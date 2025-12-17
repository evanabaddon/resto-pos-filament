const { app, BrowserWindow, ipcMain, Tray, Menu, dialog, nativeImage } = require('electron');
const path = require('path');
const fs = require('fs');
const os = require('os');
const Store = require('electron-store');
const axios = require('axios');
const ThermalPrinter = require('node-thermal-printer').printer;
const PrinterTypes = require('node-thermal-printer').types;

const store = new Store();
let mainWindow;
let tray; // Keep reference to prevent GC
let isPolling = false;
let pollingInterval;

// Default config
const DEFAULT_CONFIG = {
    webhookUrl: '',
    secretKey: '',
    printerMapping: {},

    // Template Defaults
    logoPath: '',
    tpl_receipt_showLogo: true,
    tpl_receipt_storeName: '',
    tpl_receipt_storeAddress: '',
    tpl_receipt_storePhone: '',
    tpl_receipt_footer: 'Thank You for Visiting!',
    tpl_order_bigText: true,
    tpl_order_showHeader: true,
    printerWidth: 32 // Default 58mm
};

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 900,
        height: 750,
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false
        },
        title: "Resto POS Print Agent",
        icon: path.join(__dirname, 'assets/icon.png')
    });

    mainWindow.loadFile('index.html');

    mainWindow.on('close', (event) => {
        if (!app.isQuiting) {
            event.preventDefault();

            // Show confirmation dialog before minimizing (optional, or just notify)
            // User requested: "buat konfirmasi, dan jika di close biasa maka tidak close tapi minimized"

            const choice = dialog.showMessageBoxSync(mainWindow, {
                type: 'question',
                buttons: ['Minimize to Tray', 'Quit Application'],
                defaultId: 0,
                cancelId: 0, // Esc = Minimize
                title: 'Print Agent',
                message: 'Do you want to keep the Print Agent running in the background?',
                detail: 'The application needs to stay running to process print jobs.'
            });

            if (choice === 1) {
                // Quit
                app.isQuiting = true;
                app.quit();
            } else {
                // Minimize
                mainWindow.hide();
                if (tray) {
                    tray.displayBalloon({
                        title: "Print Agent Running",
                        content: "The agent is still active in the system tray."
                    });
                }
            }
        }
        return false;
    });
}

const gotTheLock = app.requestSingleInstanceLock();

if (!gotTheLock) {
    app.quit();
} else {
    app.on('second-instance', (event, commandLine, workingDirectory) => {
        if (mainWindow) {
            if (mainWindow.isMinimized()) mainWindow.restore();
            mainWindow.show();
        }
    });

    app.whenReady().then(() => {
        if (!store.get('config')) {
            store.set('config', DEFAULT_CONFIG);
        }
        createWindow();
        createTray();

        // Auto-start if config exists
        const config = store.get('config');
        if (config.webhookUrl && config.secretKey) {
            startPolling();
        }
    });
}

function resolveIconPath() {
    // 1. Check inside app (Dev or ASAR)
    let iconPath = path.join(__dirname, 'assets/icon.png');
    if (fs.existsSync(iconPath)) return iconPath;

    // 2. Check next to executable (Unpacked or ExtraResources)
    iconPath = path.join(process.resourcesPath, 'assets/icon.png');
    if (fs.existsSync(iconPath)) return iconPath;

    // 3. Fallback (might fail but better than null)
    return path.join(__dirname, 'assets/icon.png');
}

function createTray() {
    try {
        const iconPath = resolveIconPath();
        console.log("Creating Tray with icon:", iconPath);

        if (!fs.existsSync(iconPath)) {
            console.error("CRITICAL: Tray icon NOT found at", iconPath);
            // If we throw here, app might close on minimize. 
            // We'll continue but Tray might be invisible.
        }

        tray = new Tray(iconPath);
        const contextMenu = Menu.buildFromTemplate([
            {
                label: 'Show App', click: () => {
                    if (mainWindow) mainWindow.show();
                }
            },
            { label: 'Restart Service', click: () => { stopPolling(); startPolling(); } },
            { type: 'separator' },
            {
                label: 'Quit', click: () => {
                    app.isQuiting = true;
                    app.quit();
                }
            }
        ]);

        tray.setToolTip('Resto POS Print Agent');
        tray.setContextMenu(contextMenu);

        tray.on('double-click', () => {
            if (mainWindow) mainWindow.show();
        });

        // Also restore on single click for better UX on Windows
        tray.on('click', () => {
            if (mainWindow) mainWindow.show();
        });

    } catch (e) {
        logToFile(`Tray creation failed: ${e.message}`);
        console.log("Tray creation failed:", e);
    }
}

// === LOGGING ===
function logToFile(message) {
    const timestamp = new Date().toISOString();
    const logLine = `[${timestamp}] ${message}\n`;
    const logPath = path.join(app.getPath('userData'), 'agent.log');

    fs.appendFile(logPath, logLine, (err) => {
        if (err) console.error("Failed to write to log file:", err);
    });
}


// === IPC HANDLERS ===

ipcMain.on('get-config', (event) => {
    const config = store.get('config') || DEFAULT_CONFIG;
    if (!config.printerMapping) config.printerMapping = {};
    logToFile("Config requested by UI.");
    event.reply('config-data', config);
});

ipcMain.on('get-status', (event) => {
    event.reply('status-update', isPolling ? 'Running' : 'Stopped');
});

ipcMain.on('save-config', (event, config) => {
    logToFile(`Saving Config: ${JSON.stringify(config)}`);
    store.set('config', config);
    console.log('Config saved');
    event.reply('config-saved', true);

    stopPolling();
    startPolling();
});

ipcMain.handle('get-printers', async () => {
    if (!mainWindow) return [];
    try {
        const printers = await mainWindow.webContents.getPrintersAsync();
        return printers.map(p => p.name);
    } catch (error) {
        return [];
    }
});

ipcMain.handle('select-logo', async () => {
    if (!mainWindow) return null;

    const { canceled, filePaths } = await dialog.showOpenDialog(mainWindow, {
        properties: ['openFile'],
        filters: [
            { name: 'Images', extensions: ['jpg', 'png', 'jpeg', 'bmp'] }
        ]
    });

    if (canceled || filePaths.length === 0) {
        return null;
    }

    const sourcePath = filePaths[0];
    const fileName = path.basename(sourcePath);
    const destinationFolder = app.getPath('userData'); // e.g. C:\Users\User\AppData\Roaming\resto-pos-print-agent
    const destinationPath = path.join(destinationFolder, 'logo' + path.extname(fileName)); // Keep it simple: logo.png

    try {
        fs.copyFileSync(sourcePath, destinationPath);
        return destinationPath;
    } catch (err) {
        console.error('Failed to copy logo', err);
        return sourcePath; // Fallback to original
    }
    try {
        fs.copyFileSync(sourcePath, destinationPath);
        return destinationPath;
    } catch (err) {
        console.error('Failed to copy logo', err);
        return sourcePath; // Fallback to original
    }
});

ipcMain.handle('test-print', async (event, printerName, overrideWidth) => {
    try {
        const config = store.get('config'); // Get config
        const width = overrideWidth ? parseInt(overrideWidth) : (config.printerWidth || 32);

        console.log(`Testing printer: ${printerName}, width: ${width}`);
        let printer = new ThermalPrinter({
            type: PrinterTypes.EPSON,
            interface: `printer:${printerName}`,
            driver: PrintDriver,
            width: width,
            characterSet: 'PC437_USA',
            removeSpecialCharacters: true
        });

        // Skip isPrinterConnected check for custom driver or implement it
        // PrintDriver.getPrinter returns dummy, so it should pass if we call it?
        // But ThermalPrinter.isPrinterConnected calls driver.getPrinter.
        // Let's rely on printDirect failing if printer not found.

        printer.alignCenter();
        printer.println("TEST PRINT OK");
        printer.drawLine();
        printer.println(`Printer: ${printerName}`);
        printer.println(`Width: ${width} chars`);
        printer.drawLine();
        printer.cut();
        await printer.execute();
        return { success: true };
    } catch (error) {
        console.error('Test print failed:', error);
        return { success: false, error: error.message };
    }
});

// ================== POLLING LOGIC ==================

function startPolling() {
    if (isPolling) return;
    const config = store.get('config');
    if (!config || !config.webhookUrl) return;

    isPolling = true;
    console.log('Started polling...');
    if (mainWindow) mainWindow.webContents.send('status-update', 'Running');

    // 1.5 seconds for fast restaurant response
    pollingInterval = setInterval(async () => {
        await checkPrintJobs();
    }, config.pollingInterval || 1500);
}

function stopPolling() {
    if (!isPolling) return;
    clearInterval(pollingInterval);
    isPolling = false;
    console.log('Stopped polling.');
    if (mainWindow) mainWindow.webContents.send('status-update', 'Stopped');
}

// Global deduplication set
let pendingJobs = new Set();
let isProcessing = false; // Mutex lock

async function checkPrintJobs() {
    if (isProcessing) {
        console.log('Skipping poll - previous batch still processing...');
        return;
    }

    const config = store.get('config');
    isProcessing = true;

    // Heartbeat for UI (Show user we are working)
    if (mainWindow) mainWindow.webContents.send('heartbeat', new Date().toLocaleTimeString());

    try {
        let baseUrl = config.webhookUrl.replace(/\/$/, '').replace(/\/print-jobs$/, '');

        const response = await axios.get(`${baseUrl}/print-jobs`, {
            headers: { 'X-Print-Secret': config.secretKey }
        });

        const jobs = response.data.jobs || [];
        if (jobs.length > 0) {
            console.log(`Found ${jobs.length} jobs`);
            for (const job of jobs) {
                // Deduplication Check
                if (pendingJobs.has(job.id)) {
                    console.log(`Skipping job ${job.id} - already pending`);
                    continue;
                }

                pendingJobs.add(job.id);

                try {
                    await processJob(job, config);
                } finally {
                    pendingJobs.delete(job.id);
                }

                // Delay between jobs to prevent buffer overflow / overlapping on Windows spooler
                // Windows 'print' command returns immediately, but physical print takes time.
                // We must wait for the physical device to finish.
                const jobDelay = config.printerDelay || 4000;
                console.log(`Waiting ${jobDelay}ms for printer to finish...`);
                await new Promise(resolve => setTimeout(resolve, jobDelay));
            }
        }
    } catch (error) {
        console.error('Polling error:', error.message);
        if (mainWindow) mainWindow.webContents.send('log-update', `Error: ${error.statusCode || error.message}`);
    } finally {
        isProcessing = false;
    }
}

// === DRIVER IMPLEMENTATION ===
const PrintDriver = {
    getPrinter: function (name) {
        return { status: [] }; // Verify presence handled by OS
    },
    printDirect: function (options) {
        if (process.platform === 'win32') {
            // Windows: Write directly to the UNC path (mimics PHP WindowsPrintConnector)
            // Printer MUST be Shared on Network (even if local)
            const printerName = options.printer;
            const uncPath = `\\\\localhost\\${printerName}`;

            try {
                fs.writeFileSync(uncPath, options.data);
                options.success('printed');
            } catch (err) {
                console.error('Direct print failed:', err);
                options.error(err);
            }
        } else {
            // Mac/Linux: Uses lp
            const tempFile = path.join(require('os').tmpdir(), `print_${Date.now()}_${Math.random().toString(36).substr(2, 9)}.bin`);
            fs.writeFileSync(tempFile, options.data);

            const command = `lp -d "${options.printer}" "${tempFile}"`;

            require('child_process').exec(command, (err, stdout, stderr) => {
                try { fs.unlinkSync(tempFile); } catch (e) { } // Cleanup
                if (err) {
                    options.error(err);
                } else {
                    options.success('printed');
                }
            });
        }
    }
};

async function processJob(job, config) {
    try {
        const serverPrinterName = job.printer || 'Default';
        const mapping = config.printerMapping[serverPrinterName];

        let targetPrinterName = serverPrinterName;
        let jobWidth = config.printerWidth || 32; // Default global

        // Resolve Mapping (Object or String)
        if (mapping) {
            if (typeof mapping === 'object') {
                targetPrinterName = mapping.device;
                if (mapping.width) jobWidth = mapping.width;
            } else {
                targetPrinterName = mapping; // Legacy string
            }
        }

        console.log(`Processing Job ${job.id} -> ${targetPrinterName} (Width: ${jobWidth})`);

        let printer = new ThermalPrinter({
            type: PrinterTypes.EPSON,
            interface: `printer:${targetPrinterName}`,
            driver: PrintDriver,
            width: jobWidth,
            characterSet: 'PC437_USA',
            removeSpecialCharacters: true
        });

        if (job.payload) {
            if (job.type === 'receipt') {
                await printReceiptTemplate(printer, job.payload, config, jobWidth);
            } else {
                await printOrderTemplate(printer, job.payload, job.division || 'Order', config, jobWidth);
            }
        } else {
            printer.println(job.content);
        }

        printer.cut();
        await printer.execute();

        // Complete
        let baseUrl = config.webhookUrl.replace(/\/$/, '').replace(/\/print-jobs$/, '');
        await axios.post(`${baseUrl}/print-job/${job.id}/complete`, {}, {
            headers: { 'X-Print-Secret': config.secretKey }
        });

        if (mainWindow) mainWindow.webContents.send('log-update', `Printed Job ${job.id} to ${targetPrinterName}`);

    } catch (error) {
        console.error(`Failed to print job ${job.id}:`, error);
        try {
            let baseUrl = config.webhookUrl.replace(/\/$/, '').replace(/\/print-jobs$/, '');
            await axios.post(`${baseUrl}/print-job/${job.id}/failed`, { error: error.message }, {
                headers: { 'X-Print-Secret': config.secretKey }
            });
        } catch (e) { }
    }
}

// === TEMPLATES ===

async function printReceiptTemplate(printer, data, config, width = 32) {
    const sale = data.sale;

    printer.alignCenter();

    // LOGO PRINTING
    if (config.tpl_receipt_showLogo !== false && config.logoPath) {
        await resizeAndPrintLogo(printer, config.logoPath, width);
    }

    // STORE INFO (OVERRIDE)
    const storeName = config.tpl_receipt_storeName || data.store?.name || "RESTORAN";
    const storeAddress = config.tpl_receipt_storeAddress || data.store?.address || "";
    const storePhone = config.tpl_receipt_storePhone || data.store?.phone || "";

    printer.bold(true);
    printer.println(storeName);
    printer.bold(false);

    if (storeAddress) printMultiline(printer, storeAddress); // Split \n
    if (storePhone) printer.println(storePhone);
    printer.drawLine();

    printer.alignLeft();
    printer.println(`Inv: ${sale.invoice_number}`);
    printer.println(`Date: ${new Date().toLocaleString('id-ID')}`);

    // CUSTOMER NAME (Moved to Top)
    if (sale.customer_name) {
        printer.println(`Cust: ${sale.customer_name}`);
    }

    // TABLE INFO
    if (sale.table_name) {
        printer.println(`Table: ${sale.table_name}`);
    }

    printer.drawLine();

    // Items
    data.items.forEach(item => {
        printer.alignLeft();
        printer.println(item.product_name);
        if (item.notes) printer.println(`  (${item.notes})`);

        // Dashed Line Look
        const left = `${parseInt(item.quantity)} x ${formatMoney(item.unit_price)}`;
        const right = formatMoney(item.subtotal);
        printRowSeparated(printer, left, right, width, '-');
    });

    printer.drawLine();

    // Totals
    // Subtotal
    printer.tableCustom([
        { text: "Subtotal:", align: "LEFT", width: 0.5 },
        { text: formatMoney(sale.subtotal), align: "RIGHT", width: 0.5 }
    ]);

    // Discount
    const discount = parseFloat(sale.discount_amount || sale.discount || 0);
    if (discount > 0) {
        printer.tableCustom([
            { text: "Discount:", align: "LEFT", width: 0.5 },
            { text: `-${formatMoney(discount)}`, align: "RIGHT", width: 0.5 }
        ]);
    }

    // Service? (If available in payload, otherwise skip)
    // Tax
    const tax = parseFloat(sale.tax || 0);
    if (tax > 0) {
        printer.tableCustom([
            { text: "Tax:", align: "LEFT", width: 0.5 },
            { text: formatMoney(tax), align: "RIGHT", width: 0.5 }
        ]);
    }

    // Service Charge
    const service = parseFloat(sale.service_charge || 0);
    if (service > 0) {
        printer.tableCustom([
            { text: "Service:", align: "LEFT", width: 0.5 },
            { text: formatMoney(service), align: "RIGHT", width: 0.5 }
        ]);
    }

    printer.drawLine();

    printer.tableCustom([
        { text: "Total:", align: "LEFT", width: 0.5 },
        { text: formatMoney(sale.final_total), align: "RIGHT", width: 0.5, bold: true }
    ]);

    // Payment Info
    const paid = parseFloat(sale.amount_paid) || 0;
    const total = parseFloat(sale.final_total) || 0;
    const change = paid - total;

    printer.tableCustom([
        { text: "Bayar:", align: "LEFT", width: 0.5 },
        { text: formatMoney(paid), align: "RIGHT", width: 0.5 }
    ]);

    // Kembalian (Change)
    printer.tableCustom([
        { text: "Kembali:", align: "LEFT", width: 0.5 },
        { text: formatMoney(change), align: "RIGHT", width: 0.5 }
    ]);

    // Payment Method
    if (sale.payment_method?.name) {
        printer.newLine();
        printer.alignCenter();
        printer.println(`Payment: ${sale.payment_method.name}`);
    }

    // Custom Footer / Thank You message
    printer.alignCenter();
    if (config.tpl_receipt_footer) {
        printMultiline(printer, config.tpl_receipt_footer);
    } else {
        printer.println("Terima Kasih");
    }
    // No extra newlines/cut here, processJob handles it to save paper
}

function printMultiline(printer, text) {
    if (!text) return;
    const lines = text.split(/\r?\n/);
    lines.forEach(line => printer.println(line));
}

function printRowSeparated(printer, left, right, width, char = '-') {
    const lenLeft = left.length;
    const lenRight = right.length;
    // 2 spaces padding around separator
    const available = width - lenLeft - lenRight - 2;

    if (available > 0) {
        const sep = char.repeat(available);
        printer.println(`${left} ${sep} ${right}`);
    } else {
        // Not enough space, just space it
        printer.tableCustom([
            { text: left, align: 'LEFT', width: 0.6 },
            { text: right, align: 'RIGHT', width: 0.4 }
        ]);
    }
}

async function printOrderTemplate(printer, data, division, config, width) {
    const qtySize = config.tpl_order_qtySize || '2,2';
    const prodSize = config.tpl_order_productSize || '1,1_BOLD';
    const showHeader = config.tpl_order_showHeader !== false;
    const showTime = config.tpl_order_showTime !== false;
    const showCust = config.tpl_order_showCust !== false;
    const showTable = config.tpl_order_showTable !== false;

    // 1. HEADER
    printer.alignCenter();
    if (showHeader) {
        printer.bold(true);
        printer.setTextSize(1, 1); // Double size untuk header
        printer.println(` ${division.toUpperCase()} `);
        printer.setTextSize(0, 0); // Reset ke normal
        printer.bold(false);
    }
    printer.newLine();
    // 2. META INFO
    printer.alignLeft();

    if (showTime) {
        printer.println(`Time: ${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`);
    }

    // CUSTOMER NAME
    const custName = data.sale?.customer_name || data.customer_name || data.customer;
    if (showCust && custName) {
        printer.bold(true);
        printer.println(`CUST: ${custName}`);
        printer.bold(false);
    }

    // TABLE
    const tableNum = data.table || (data.sale && data.sale.table_name) || (data.sale && data.sale.table_number);
    if (showTable && tableNum) {
        printer.bold(true);
        printer.println(`TABLE: ${tableNum}`);
        printer.bold(false);
    }

    printer.drawLine();

    // 3. ITEMS - dengan ukuran teks yang sesuai
    if (Array.isArray(data.items)) {
        // Gunakan ukuran yang sama dengan produk untuk notes agar jelas (atau bisa dibuat config sendiri)
        const noteSize = config.tpl_order_noteSize || config.tpl_order_productSize || '1,1_BOLD';

        data.items.forEach(item => {
            // Print quantity dengan ukuran besar
            applyTextSize(printer, qtySize);
            printer.print(`${parseInt(item.quantity)} `);

            // Print product name dengan ukuran yang ditentukan
            applyTextSize(printer, prodSize);
            printer.println(item.product_name);

            // Print NOTES dengan ukuran besar juga (tidak reset ke normal)
            if (item.notes) {
                applyTextSize(printer, noteSize); // Apply size untuk note
                printer.print("   "); // Indent note
                printer.println(`NOTE: ${item.notes}`);
            }

            // Reset ke normal hanya setelah item selesai (sebelum garis atau item berikutnya)
            printer.setTextNormal();
            printer.drawLine();
        });
    } else {
        printer.println("[ NO ITEMS DATA ]");
    }

    // Reset ke normal di akhir
    printer.setTextNormal();
}

// Simplified Row Printer (No resizing logic for now)
function printOrderRowSafe(printer, item) {
    const qtyText = `${parseInt(item.quantity)} `;
    const prodText = item.product_name;

    printer.alignLeft();
    printer.setTextNormal(); // SAFE MODE

    // Qty
    // printer.bold(true);
    printer.print(qtyText);
    // printer.bold(false);

    // Product
    // We just print it next to it. wrapping handled by printer usually or we can simple wrap.
    // For safety, just print.
    printer.println(prodText);
}

function printOrderRow(printer, item, qtySize, prodSize, totalWidth) {
    // Deprecated/Unused for now to fix crash
    printOrderRowSafe(printer, item);
}

function getWidthScale(sizeString) {
    // scale 1 for 1,1 or 0,1 (double height)
    // scale 2 for 1,0 (double width) or 2,2
    const [dim] = sizeString.split('_');
    const [w, h] = dim.split(',').map(x => parseInt(x));

    // Usually node-thermal-printer logic:
    // If w=2 (double width), it takes double space.
    // Wait, w parameter in setTextSize(w, h). 
    // Manual says: setTextSize(height, width).
    // Let's re-read applyTextSize I wrote:
    // printer.setTextSize(h, w); -> So w is width factor?
    // If I passed "2,2" -> w=2, h=2. setTextSize(2,2). Width is double.

    if (w >= 2) return w;
    return 1;
}

function applyTextSize(printer, sizeString) {
    if (!sizeString) sizeString = "1,1";

    // Format: "1,1" atau "1,1_BOLD"
    const [dim, extra] = sizeString.split('_');
    let [w, h] = dim.split(',').map(x => parseInt(x));

    // Library node-thermal-printer menggunakan setTextSize(height, width)
    // Nilai: 0=normal, 1=double height, 2=double width, 3=double both
    // Kita ubah skala 1,2,3 menjadi 0,1,2

    // Untuk skala custom:
    // w=1, h=1 → normal (0,0)
    // w=2, h=1 → double width (0,1)
    // w=1, h=2 → double height (1,0)
    // w=2, h=2 → double both (1,1)
    // w=3, h=3 → triple both (2,2)

    const widthScale = Math.max(0, w - 1);
    const heightScale = Math.max(0, h - 1);

    // Gunakan setTextSize untuk ukuran custom
    printer.setTextSize(heightScale, widthScale);

    // Handle bold terpisah
    if (extra === 'BOLD') {
        printer.bold(true);
    } else {
        printer.bold(false);
    }
}

function formatMoney(amount) {
    return new Intl.NumberFormat('id-ID').format(parseFloat(amount) || 0);
}

async function resizeAndPrintLogo(printer, logoPath, widthChars) {
    try {
        if (!fs.existsSync(logoPath)) return;

        // Calculate target width in pixels
        // 58mm ~ 384 dots
        // 80mm ~ 576 dots
        // widthChars 32 ~ 58mm
        // widthChars 42/48 ~ 80mm

        let targetWidth = 384;
        if (widthChars >= 40) targetWidth = 512; // Conservative for 80mm

        const image = nativeImage.createFromPath(logoPath);
        if (image.isEmpty()) return;

        const resized = image.resize({ width: targetWidth });
        const tempPath = path.join(os.tmpdir(), `logo-${Date.now()}.png`);

        fs.writeFileSync(tempPath, resized.toPNG());

        await printer.printImage(tempPath);

        // Cleanup asynchronously
        setTimeout(() => {
            try { fs.unlinkSync(tempPath); } catch (e) { }
        }, 1000);

    } catch (e) {
        console.error("Resize Logo Error:", e);
        printer.println("[LOGO ERROR]");
    }
}
