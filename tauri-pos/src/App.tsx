import { useState, useEffect, useRef } from 'react';
import { api, updateApiConfig } from './services/api';
import { dbService } from './services/db';
import { syncService } from './services/sync';
import { printerService, type PrinterSettings } from './services/printer';
// Components
import { TopBar } from './components/TopBar';
import { ProductGrid } from './components/ProductGrid';
import { CartSidebar } from './components/CartSidebar';
import ShiftModal from './components/ShiftModal';
import PaymentModal from './components/PaymentModal';
import Notification from './components/Notification';
import ConfirmModal from './components/ConfirmModal';
import type { Product, Category, CartItem } from './types';


function App() {
    const [products, setProducts] = useState<Product[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [cart, setCart] = useState<CartItem[]>([]);
    const [selectedCategory, setSelectedCategory] = useState<number | 'SEMUA'>('SEMUA');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [orderNumber] = useState(() => Date.now().toString().slice(-6));
    const [customerName, setCustomerName] = useState('');
    const [orderType, setOrderType] = useState<'Dine In' | 'Take Away'>('Dine In');
    const [tableNumber, setTableNumber] = useState('');
    const [discount, setDiscount] = useState<number>(0);
    const [isSyncing, setIsSyncing] = useState(false);
    const [pendingCount, setPendingCount] = useState(0);

    // Notification State
    const [notification, setNotification] = useState<{ message: string, type: 'success' | 'error' | 'info' } | null>(null);

    const showNotification = (message: string, type: 'success' | 'error' | 'info') => {
        setNotification({ message, type });
    };

    // Confirm Modal State
    const [confirmModal, setConfirmModal] = useState<{
        isOpen: boolean;
        title: string;
        message: string;
        onConfirm: () => void;
        isDestructive?: boolean;
    }>({
        isOpen: false,
        title: '',
        message: '',
        onConfirm: () => { },
        isDestructive: false
    });

    const closeConfirmModal = () => {
        setConfirmModal(prev => ({ ...prev, isOpen: false }));
    };

    // Draft Tracking
    const [activeDraft, setActiveDraft] = useState<{ id: number, source: 'local' | 'server' } | null>(null);

    // Shift Management
    const [activeShift, setActiveShift] = useState<any>(null); // { id, cashier_name, cash_in_hand, etc }
    const [isShiftModalOpen, setIsShiftModalOpen] = useState(false);
    const [shiftModalMode, setShiftModalMode] = useState<'open' | 'close'>('open');

    // Payment Modal
    const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
    const [paymentMethods, setPaymentMethods] = useState<any[]>([]);

    const handleShiftOpened = (shift: any) => {
        setActiveShift(shift);
        setIsShiftModalOpen(false);
        handleSync(); // Trigger sync immediately
    };

    const handleShiftClosed = () => {
        setActiveShift(null);
        setIsShiftModalOpen(false);
        handleSync(); // Trigger sync immediately
    };

    const handlePrintShiftReport = async (shiftData: any) => {
        if (!printerSettings.cashierPrinter) return;
        try {
            const reportText = printerService.generateShiftReportText(
                shiftData,
                settings,
                printerSettings.cashierPaperWidth || '58mm'
            );
            await printerService.printJob(printerSettings.cashierPrinter, reportText);
        } catch (e) {
            console.error('Failed to print shift report:', e);
            showNotification('Gagal mencetak laporan shift', 'error');
        }
    };

    // Business Settings
    const [settings, setSettings] = useState<any>(() => {
        const stored = localStorage.getItem('pos_settings');
        return stored ? JSON.parse(stored) : { tax_rate: 0 };
    });

    const [showSettings, setShowSettings] = useState(false);
    const [settingsTab, setSettingsTab] = useState<'general' | 'printer'>('general');
    const [settingsApiUrl, setSettingsApiUrl] = useState('');

    // Printer Settings
    const [availablePrinters, setAvailablePrinters] = useState<string[]>([]);
    const [newMapping, setNewMapping] = useState<{ categoryId: string, printerName: string, paperWidth: '58mm' | '80mm' }>({ categoryId: '', printerName: '', paperWidth: '58mm' });
    const [printerSettings, setPrinterSettings] = useState<PrinterSettings>(() => {
        const stored = localStorage.getItem('pos_printer_settings');
        // Migrate old settings if needed or just use new default
        if (stored) {
            const parsed = JSON.parse(stored);
            return {
                cashierPrinter: parsed.cashierPrinter || '',
                cashierPaperWidth: parsed.cashierPaperWidth || '58mm',
                autoPrint: parsed.autoPrint || false,
                categoryMappings: parsed.categoryMappings || []
            };
        }
        return {
            cashierPrinter: '',
            cashierPaperWidth: '58mm',
            autoPrint: false,
            categoryMappings: []
        };
    });

    // Load available printers when settings open
    useEffect(() => {
        if (showSettings) {
            printerService.getPrinters().then(setAvailablePrinters);
        }
    }, [showSettings]);

    const handleSaveSettings = () => {
        updateApiConfig(settingsApiUrl);
        localStorage.setItem('pos_printer_settings', JSON.stringify(printerSettings));
        showNotification('⚙️ Pengaturan tersimpan!', 'success');
        setShowSettings(false);
        // Trigger sync with new URL
        handleSync();
    };

    // Print State
    const [printOrder, setPrintOrder] = useState<any>(null);

    // Auto Print Effect
    useEffect(() => {
        if (printOrder) {
            setTimeout(() => {
                window.print();
                setPrintOrder(null); // Reset after print dialog opens
            }, 500); // Small delay to ensure render
        }
    }, [printOrder]);

    // Product Search State
    const [searchQuery, setSearchQuery] = useState('');
    const searchInputRef = useRef<HTMLInputElement>(null);

    // Initial Load Settings
    useEffect(() => {
        const storedUrl = localStorage.getItem('pos_api_url');
        // @ts-ignore
        const defaultUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
        setSettingsApiUrl(storedUrl || defaultUrl);
    }, []);



    // Keyboard Shortcuts
    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if ((e.ctrlKey && e.key === 'k') || e.key === '/') {
                e.preventDefault();
                searchInputRef.current?.focus();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

    // INITIALIZATION & SYNC
    useEffect(() => {
        const initApp = async () => {
            try {
                setLoading(true);
                // 1. Init DB
                await dbService.init();

                // 2. Load Local Data First (Instant Load)
                await loadLocalData();

                // 3. Check for Open Shift
                const openShift = await dbService.getOpenShift();
                if (openShift) {
                    console.log('✅ Found active shift:', openShift);
                    setActiveShift(openShift);
                } else {
                    console.log('ℹ️ No active shift found.');
                    // Force Open Shift Modal
                    setShiftModalMode('open');
                    setIsShiftModalOpen(true);
                }

                // 4. Trigger Background Sync
                handleSync();

            } catch (err: any) {
                console.error('App init failed:', err);
                setError(`Gagal memuat database lokal: ${err.message || JSON.stringify(err)}`);
            } finally {
                setLoading(false);
            }
        };

        initApp();
    }, []);

    const loadLocalData = async () => {
        const localProducts = await dbService.getProducts();
        const localCategories = await dbService.getCategories();
        const localPaymentMethods = await dbService.getPaymentMethods();
        setProducts(localProducts);
        setCategories(localCategories);
        setPaymentMethods(localPaymentMethods);

        // Load Pending Sales Count
        try {
            const pendingSales = await dbService.getPendingSales();
            // Filter only 'pending' status for upload queue (drafts might be local only?)
            // But if getPendingSales returns drafts too, maybe we just count 'pending'.
            // Let's count all returned by getPendingSales as they are candidates for sync.
            // Actually, let's filter just to be safe if we only want 'pending'.
            // Based on db.ts: status IN('pending', 'draft'). 
            // If we want "Pending Uploads", usually means completed sales.
            const pendingUploads = pendingSales.filter(s => s.status === 'pending');
            setPendingCount(pendingUploads.length);
        } catch (e) {
            console.error('Failed to load pending count:', e);
        }

        // Load Settings
        const storedSettings = localStorage.getItem('pos_settings');
        if (storedSettings) {
            setSettings(JSON.parse(storedSettings));
        }
    };

    const handleSync = async () => {
        setIsSyncing(true);
        try {
            await syncService.syncSettings(); // Sync Settings first

            // Upload Strategy: Send local changes FIRST so server is up to date
            await syncService.syncShifts();
            await syncService.syncSales();

            // Download Strategy: Get latest data (reflecting our uploads + others)
            await syncService.syncProducts();
            await syncService.syncSalesHistory(activeShift?.id); // Download today's sales for backup and assign to current shift
        } catch (error) {
            console.error('Manual sync failed:', error);
        } finally {
            // Refresh local view regardless of sync success/failure
            await loadLocalData();
            setIsSyncing(false);
        }
    };

    const addToCart = (product: Product) => {
        // Stock Check
        if (product.stock !== undefined && product.stock <= 0) {
            showNotification(`Stok habis untuk ${product.name}!`, 'error');
            return;
        }

        const existingItem = cart.find(item => item.product.id === product.id);

        if (existingItem) {
            // Check if adding 1 exceeds stock
            if (product.stock !== undefined && existingItem.quantity + 1 > product.stock) {
                showNotification(`Stok tidak cukup! Sisa: ${product.stock}`, 'error');
                return;
            }

            setCart(cart.map(item =>
                item.product.id === product.id
                    ? { ...item, quantity: item.quantity + 1, subtotal: (item.quantity + 1) * product.price }
                    : item
            ));
        } else {
            setCart([...cart, {
                product,
                quantity: 1,
                subtotal: Number(product.price),
            }]);
        }
    };

    const removeFromCart = (productId: number) => {
        setCart(cart.filter(item => item.product.id !== productId));
    };

    const updateQuantity = (productId: number, quantity: number) => {
        if (quantity <= 0) {
            removeFromCart(productId);
            return;
        }

        const item = cart.find(i => i.product.id === productId);
        if (item && item.product.stock !== undefined) {
            if (quantity > item.product.stock) {
                showNotification(`Stok maksimum tercapai! Sisa: ${item.product.stock}`, 'error');
                return;
            }
        }

        setCart(cart.map(item =>
            item.product.id === productId
                ? { ...item, quantity, subtotal: quantity * item.product.price }
                : item
        ));
    };

    const updateItemNotes = (productId: number, notes: string) => {
        setCart(cart.map(item =>
            item.product.id === productId
                ? { ...item, notes }
                : item
        ));
    };

    const calculateTotal = (): number => {
        const subtotal = cart.reduce((sum, item) => sum + Number(item.subtotal), 0);
        const taxRate = settings?.tax_rate || 0;
        const tax = (subtotal * taxRate) / 100;
        return subtotal + tax - (Number(discount) || 0);
    };

    // DRAFTS SYSTEM
    const [showDrafts, setShowDrafts] = useState(false);
    const [transactionTab, setTransactionTab] = useState<'draft' | 'completed'>('draft');
    const [drafts, setDrafts] = useState<{ local_id: number, sale_data: any, created_at: string }[]>([]);

    const loadDrafts = async (status: 'draft' | 'completed' = 'draft') => {
        const allDrafts: any[] = [];

        // 1. Local Drafts/Completed
        const localDrafts = await dbService.getDrafts(status);
        localDrafts.forEach(d => {
            allDrafts.push({
                id: d.local_id,
                source: 'local',
                data: JSON.parse(d.sale_data),
                created_at: d.created_at
            });
        });

        // 2. Server Drafts (only if tab is 'draft')
        if (status === 'draft') {
            try {
                const res = await api.getDrafts();
                if (res.data && res.data.success) {
                    res.data.drafts.forEach((d: any) => {
                        allDrafts.push({
                            id: d.server_id,
                            source: 'server',
                            data: d.sale_data,
                            created_at: d.created_at
                        });
                    });
                }
            } catch (e) {
                console.error('Failed to load server drafts:', e);
            }
        }

        // Sort by newest
        allDrafts.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
        setDrafts(allDrafts);
    };

    const handleSaveDraft = async () => {
        if (cart.length === 0) return;

        if (!customerName.trim()) {
            showNotification('⚠️ Harap isi Nama Pelanggan untuk menyimpan transaksi!', 'error');
            return;
        }

        const subtotal = cart.reduce((sum, item) => sum + Number(item.subtotal), 0);
        const taxRate = settings?.tax_rate || 0;
        const tax = (subtotal * taxRate) / 100;

        const saleData = {
            items: cart.map(item => ({
                product_id: item.product.id,
                quantity: item.quantity,
                price: item.product.price,
                subtotal: item.subtotal,
                notes: item.notes || null,
                product_name: item.product.name
            })),
            subtotal: subtotal,
            tax: tax,
            discount: discount,
            total: calculateTotal(),
            payment_method: null, // Draft hasn't paid yet
            customer_name: customerName,
            order_type: orderType,
            table_number: orderType === 'Dine In' ? tableNumber : null,
            member_id: null, // Simplified for now
            created_at: new Date().toISOString()
        };

        try {
            // If editing an existing draft, delete the old one first (Replace)
            if (activeDraft) {
                try {
                    if (activeDraft.source === 'local') {
                        await dbService.deleteSale(activeDraft.id);
                    } else {
                        await api.deleteDraft(activeDraft.id);
                    }
                } catch (e) {
                    console.warn('Gagal menghapus draft lama, mungkin akan duplikat:', e);
                }
            }

            await dbService.saveOfflineSale(saleData, true); // true = IS DRAFT
            showNotification('✅ Draft Berhasil Disimpan ' + (activeDraft ? '(Diperbarui)' : '') + '!', 'success');

            setCart([]);
            setCustomerName('');
            setTableNumber('');
            setDiscount(0);
            setActiveDraft(null); // Reset active draft

            // Refresh products to show updated stock
            await loadLocalData();
        } catch (err: any) {
            showNotification('❌ Gagal simpan draft: ' + err.message, 'error');
        }
    };

    const handleResumeDraft = async (draft: any) => {
        if (cart.length > 0) {
            setConfirmModal({
                isOpen: true,
                title: 'Timpa Keranjang?',
                message: 'Keranjang saat ini tidak kosong. Apakah Anda yakin ingin menggantinya dengan draft ini?',
                isDestructive: true,
                onConfirm: () => processResumeDraft(draft)
            });
            return;
        }
        processResumeDraft(draft);
    };

    const processResumeDraft = async (draft: any) => {
        closeConfirmModal();

        try {
            // Restore Cart
            const restoredCart: CartItem[] = draft.data.items.map((item: any) => ({
                product: {
                    id: item.product_id,
                    name: item.product_name,
                    price: Number(item.price),
                    // Mock other fields if needed or load from products array
                } as Product,
                quantity: Number(item.quantity),
                subtotal: Number(item.subtotal),
                notes: item.notes
            }));

            setCart(restoredCart);
            setCustomerName(draft.data.customer_name || '');
            setOrderType(draft.data.order_type || 'Dine In');
            setTableNumber(draft.data.table_number || '');
            setDiscount(Number(draft.data.discount || 0));

            // Track this draft so we can delete it only after Checkout or Update
            setActiveDraft({ id: draft.id, source: draft.source });

            setShowDrafts(false);

            showNotification('✅ Draft Berhasil Diresume!', 'info');

        } catch (err) {
            console.error('Failed to resume draft:', err);
            showNotification('❌ Gagal resume draft', 'error');
        }
    };

    const handleDeleteDraft = (draft: any, e: React.MouseEvent) => {
        e.stopPropagation(); // Prevent resume triggger

        setConfirmModal({
            isOpen: true,
            title: 'Hapus Draft?',
            message: 'Draft ini akan dihapus secara permanen dan tidak dapat dikembalikan.',
            isDestructive: true,
            onConfirm: async () => {
                closeConfirmModal();
                console.log('🗑️ Deleting draft:', draft);

                if (!draft.id) {
                    showNotification('❌ Error: Draft ID is missing/undefined', 'error');
                    return;
                }

                try {
                    if (draft.source === 'local') {
                        await dbService.deleteSale(draft.id);
                    } else {
                        await api.deleteDraft(draft.id);
                    }
                    // Refresh list
                    loadDrafts(transactionTab);
                    // Refresh products to restore stock display
                    await loadLocalData();
                    showNotification('🗑️ Draft dihapus', 'success');
                } catch (err: any) {
                    showNotification('❌ Gagal menghapus draft: ' + (err.message || 'Error'), 'error');
                }
            }
        });
    };

    // HYBRID CHECKOUT
    const handleCheckout = async () => {
        if (!activeShift) {
            showNotification('⚠️ Harap Buka Shift Terlebih Dahulu!', 'error');
            setShiftModalMode('open');
            setIsShiftModalOpen(true);
            return;
        }

        if (cart.length === 0) {
            showNotification('Cart is empty!', 'error');
            return;
        }

        if (!customerName.trim()) {
            showNotification('⚠️ Harap isi Nama Pelanggan untuk melanjutkan pembayaran!', 'error');
            return;
        }

        // Open Payment Modal
        setIsPaymentModalOpen(true);
    };

    const handlePaymentConfirm = async (amount: number, methodId: number, methodCode: string) => {
        setIsPaymentModalOpen(false);

        const subtotal = cart.reduce((sum, item) => sum + Number(item.subtotal), 0);
        const taxRate = settings?.tax_rate || 0;
        const tax = (subtotal * taxRate) / 100;
        const total = subtotal + tax - discount;

        const saleData = {
            items: cart.map(item => ({
                product_id: item.product.id,
                quantity: item.quantity,
                price: item.product.price,
                subtotal: item.subtotal,
                notes: item.notes || null,
                product_name: item.product.name // Save name for offline receipt
            })),
            subtotal: subtotal,
            tax: tax,
            discount: discount,
            total: total,
            payment_method_id: methodId,
            payment_amount: amount,
            change: methodCode === 'cash' ? amount - total : 0,
            status: 'completed',         // Mark as COMPLETED locally
            created_at: new Date().toISOString(),
            customer_name: customerName,
            order_type: orderType,
            table_number: orderType === 'Dine In' ? tableNumber : null,
            member_id: null
        };

        try {
            // 1. Save to Local DB (Offline First) -> Status 'pending' (Ready for sync)
            const localId = await dbService.saveOfflineSale(saleData, false, activeShift?.id);

            // If this was a resumed draft, delete the original draft now
            if (activeDraft) {
                try {
                    if (activeDraft.source === 'local') {
                        await dbService.deleteSale(activeDraft.id);
                    } else {
                        await api.deleteDraft(activeDraft.id);
                    }
                } catch (e) {
                    console.error('Failed to delete original draft after checkout', e);
                }
                setActiveDraft(null);
            }

            // Trigger Print logic
            if (printerSettings.autoPrint && printerSettings.cashierPrinter) {
                try {
                    const invoiceNum = `OFFLINE-${localId}`;
                    const receiptText = printerService.generateReceiptText(
                        { ...saleData, invoice_number: invoiceNum },
                        settings,
                        printerSettings.cashierPaperWidth || '58mm'
                    );

                    // 1. Print to Cashier
                    await printerService.printJob(printerSettings.cashierPrinter, receiptText);

                    // 2. Process Mappings (Kitchen/Bar/etc)
                    const printerGroups: Record<string, { items: any[], paperWidth: '58mm' | '80mm' }> = {};

                    saleData.items.forEach((item: any) => {
                        const mapping = printerSettings.categoryMappings && printerSettings.categoryMappings.find(m => m.categoryId === item.category_id);
                        if (mapping && mapping.printerName) {
                            if (!printerGroups[mapping.printerName]) {
                                printerGroups[mapping.printerName] = {
                                    items: [],
                                    paperWidth: mapping.paperWidth || '58mm'
                                };
                            }
                            printerGroups[mapping.printerName].items.push(item);
                        }
                    });

                    // Print each group
                    for (const [targetPrinter, group] of Object.entries(printerGroups)) {
                        const ticketOrder = {
                            ...saleData,
                            invoice_number: invoiceNum,
                            items: group.items,
                            subtotal: 0, tax: 0, discount: 0, total: 0
                        };
                        const ticketText = printerService.generateReceiptText(ticketOrder, settings, group.paperWidth);
                        await printerService.printJob(targetPrinter, ticketText);
                    }

                    showNotification(`✅ Order Berhasil Disimpan & Dicetak! (Total: Rp ${total.toLocaleString('id-ID')})`, 'success');
                } catch (printErr: any) {
                    console.error('Direct print failed, falling back to dialog', printErr);
                    showNotification('⚠️ Gagal Direct Print: ' + printErr, 'error');
                    setPrintOrder({ ...saleData, invoice_number: `OFFLINE-${localId}` });
                }
            } else {
                // Browser Print
                setPrintOrder({ ...saleData, invoice_number: `OFFLINE-${localId}` });
            }

            // 2. Clear Cart
            setCart([]);
            setCustomerName('');
            setTableNumber('');
            setDiscount(0);
            setActiveDraft(null);

            // 3. Try Background Sync
            handleSync();

        } catch (err: any) {
            console.error('Checkout failed:', err);
            showNotification('❌ Gagal menyimpan data transaksi: ' + err.message, 'error');
        }
    };

    const filteredProducts = products.filter(p => {
        const matchesCategory = selectedCategory === 'SEMUA' || p.category_id === selectedCategory;
        const matchesSearch = p.name.toLowerCase().includes(searchQuery.toLowerCase());
        return matchesCategory && matchesSearch;
    });

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-screen bg-gray-100 flex-col">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mb-4"></div>
                <p className="text-gray-600">Memuat Sistem POS...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="flex items-center justify-center min-h-screen bg-red-50 p-8">
                <div className="bg-white p-6 rounded-lg shadow-lg max-w-md w-full text-center">
                    <h2 className="text-xl font-bold text-red-600 mb-2">System Error</h2>
                    <p className="text-gray-600 mb-4">{error}</p>
                    <button onClick={() => window.location.reload()} className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        Reload App
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex h-screen bg-gray-100 overflow-hidden font-sans text-gray-900">
            {/* LEFT: Products Section */}
            <div className="flex-1 flex flex-col min-w-0">
                <TopBar
                    isSyncing={isSyncing}
                    pendingCount={pendingCount}
                    searchQuery={searchQuery}
                    setSearchQuery={setSearchQuery}
                    onSearchInputRef={searchInputRef}
                    onOpenDrafts={() => { loadDrafts(); setShowDrafts(true); }}
                    activeShift={activeShift}
                    onToggleShift={() => {
                        if (activeShift) {
                            setShiftModalMode('close');
                            setIsShiftModalOpen(true);
                        } else {
                            setShiftModalMode('open');
                            setIsShiftModalOpen(true);
                        }
                    }}
                    onManualSync={handleSync}
                    onOpenSettings={() => setShowSettings(true)}
                />

                {/* SETTINGS MODAL */}
                {showSettings && (
                    <div className="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4">
                        <div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-0 overflow-hidden flex flex-col max-h-[90vh]">
                            <div className="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                <h2 className="text-lg font-bold">⚙️ Pengaturan</h2>
                                <button onClick={() => setShowSettings(false)} className="text-gray-500 hover:text-gray-700">✕</button>
                            </div>

                            <div className="flex border-b border-gray-200">
                                <button
                                    onClick={() => setSettingsTab('general')}
                                    className={`flex-1 py-3 text-sm font-medium transition-colors ${settingsTab === 'general' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-700 bg-gray-50'}`}
                                >
                                    Umum
                                </button>
                                <button
                                    onClick={() => setSettingsTab('printer')}
                                    className={`flex-1 py-3 text-sm font-medium transition-colors ${settingsTab === 'printer' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-700 bg-gray-50'}`}
                                >
                                    Printer & Struk
                                </button>
                            </div>

                            <div className="p-6 overflow-y-auto">
                                {settingsTab === 'general' ? (
                                    <div className="space-y-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Server API URL</label>
                                            <div className="flex gap-2">
                                                <input
                                                    type="text"
                                                    className="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-500"
                                                    value={settingsApiUrl}
                                                    onChange={(e) => setSettingsApiUrl(e.target.value)}
                                                    placeholder="http://localhost:8000/api"
                                                />
                                                <button
                                                    onClick={async () => {
                                                        try {
                                                            const res = await api.testConnection(settingsApiUrl);
                                                            alert(`✅ Terhubung!\nServer: ${res.data.message}\nWaktu: ${res.data.time}`);
                                                        } catch (e: any) {
                                                            alert(`❌ Gagal terhubung: ${e.message}`);
                                                        }
                                                    }}
                                                    className="px-3 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                                                >
                                                    📡 Tes
                                                </button>
                                            </div>
                                            <p className="text-xs text-gray-500 mt-1">Pastikan URL diakhiri dengan <code>/api</code></p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {/* Main Cashier Printer */}
                                        <div className="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                            <label className="block text-sm font-bold text-gray-700 mb-1">Printer Kasir (Utama)</label>
                                            <div className="flex gap-2 mb-2">
                                                <select
                                                    className="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-500"
                                                    value={printerSettings.cashierPrinter}
                                                    onChange={(e) => setPrinterSettings({ ...printerSettings, cashierPrinter: e.target.value })}
                                                >
                                                    <option value="">-- Pilih Printer --</option>
                                                    {availablePrinters.map(p => <option key={p} value={p}>{p}</option>)}
                                                </select>
                                                <select
                                                    className="w-24 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-500"
                                                    value={printerSettings.cashierPaperWidth || '58mm'}
                                                    onChange={(e) => setPrinterSettings({ ...printerSettings, cashierPaperWidth: e.target.value as '58mm' | '80mm' })}
                                                >
                                                    <option value="58mm">58mm</option>
                                                    <option value="80mm">80mm</option>
                                                </select>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    id="autoPrint"
                                                    checked={printerSettings.autoPrint}
                                                    onChange={(e) => setPrinterSettings({ ...printerSettings, autoPrint: e.target.checked })}
                                                    className="rounded text-primary-600 focus:ring-primary-500"
                                                />
                                                <label htmlFor="autoPrint" className="text-sm font-medium text-gray-700">Otomatis Cetak (Direct Print)</label>
                                            </div>
                                            <p className="text-xs text-gray-500 mt-1 ml-6">Jika aktif, struk langsung dicetak tanpa dialog Windows.</p>
                                        </div>

                                        <hr className="my-2 border-gray-200" />

                                        {/* Category Mappings */}
                                        <div>
                                            <h3 className="text-sm font-bold text-gray-800 mb-2">🔀 Mapping Printer Kategori (Dapur/Bar/dll)</h3>
                                            <p className="text-xs text-gray-500 mb-3">Item dalam kategori ini akan dicetak terpisah ke printer yang dipilih (Tiket Pesanan).</p>

                                            <div className="border border-gray-200 rounded-lg overflow-hidden">
                                                <table className="w-full text-sm text-left">
                                                    <thead className="bg-gray-50 text-gray-600 font-medium">
                                                        <tr>
                                                            <th className="px-3 py-2">Kategori</th>
                                                            <th className="px-3 py-2">Target Printer</th>
                                                            <th className="px-3 py-2">Size</th>
                                                            <th className="px-3 py-2 w-10"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-gray-100">
                                                        {printerSettings.categoryMappings && printerSettings.categoryMappings.map((mapping, idx) => {
                                                            const catName = categories.find(c => c.id === mapping.categoryId)?.name || `ID: ${mapping.categoryId}`;
                                                            return (
                                                                <tr key={idx} className="hover:bg-gray-50">
                                                                    <td className="px-3 py-2">{catName}</td>
                                                                    <td className="px-3 py-2 font-mono text-xs">{mapping.printerName}</td>
                                                                    <td className="px-3 py-2 font-mono text-xs">{mapping.paperWidth || '58mm'}</td>
                                                                    <td className="px-3 py-2 text-center">
                                                                        <button
                                                                            onClick={() => {
                                                                                const newMappings = [...printerSettings.categoryMappings];
                                                                                newMappings.splice(idx, 1);
                                                                                setPrinterSettings({ ...printerSettings, categoryMappings: newMappings });
                                                                            }}
                                                                            className="text-red-500 hover:text-red-700 font-bold"
                                                                            title="Hapus Mapping"
                                                                        >
                                                                            ✕
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            );
                                                        })}
                                                        {(!printerSettings.categoryMappings || printerSettings.categoryMappings.length === 0) && (
                                                            <tr>
                                                                <td colSpan={4} className="px-3 py-4 text-center text-gray-400 italic">Belum ada mapping printer.</td>
                                                            </tr>
                                                        )}
                                                    </tbody>
                                                </table>

                                                {/* Add New Mapping Form */}
                                                <div className="bg-gray-50 p-3 border-t border-gray-200 flex gap-2 items-center">
                                                    <select
                                                        className="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded"
                                                        value={newMapping.categoryId}
                                                        onChange={(e) => setNewMapping({ ...newMapping, categoryId: e.target.value })}
                                                    >
                                                        <option value="">Pilih Kategori...</option>
                                                        {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                                    </select>
                                                    <span className="text-gray-400">➜</span>
                                                    <select
                                                        className="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded"
                                                        value={newMapping.printerName}
                                                        onChange={(e) => setNewMapping({ ...newMapping, printerName: e.target.value })}
                                                    >
                                                        <option value="">Pilih Printer...</option>
                                                        {availablePrinters.map(p => <option key={p} value={p}>{p}</option>)}
                                                    </select>
                                                    <select
                                                        className="w-20 px-2 py-1.5 text-sm border border-gray-300 rounded"
                                                        value={newMapping.paperWidth}
                                                        onChange={(e) => setNewMapping({ ...newMapping, paperWidth: e.target.value as '58mm' | '80mm' })}
                                                    >
                                                        <option value="58mm">58mm</option>
                                                        <option value="80mm">80mm</option>
                                                    </select>
                                                    <button
                                                        onClick={() => {
                                                            if (!newMapping.categoryId || !newMapping.printerName) return;
                                                            const catId = Number(newMapping.categoryId);
                                                            // Avoid duplicates
                                                            const exists = printerSettings.categoryMappings.some(m => m.categoryId === catId);
                                                            if (exists) {
                                                                alert('Kategori ini sudah memiliki mapping!');
                                                                return;
                                                            }
                                                            setPrinterSettings({
                                                                ...printerSettings,
                                                                categoryMappings: [
                                                                    ...printerSettings.categoryMappings,
                                                                    {
                                                                        categoryId: catId,
                                                                        printerName: newMapping.printerName,
                                                                        paperWidth: newMapping.paperWidth
                                                                    }
                                                                ]
                                                            });
                                                            setNewMapping({ categoryId: '', printerName: '', paperWidth: '58mm' });
                                                        }}
                                                        disabled={!newMapping.categoryId || !newMapping.printerName}
                                                        className="bg-green-600 text-white px-3 py-1.5 rounded text-sm font-bold hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                                                    >
                                                        + Tambah
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="p-4 border-t border-gray-100 flex justify-end gap-2 bg-gray-50">
                                <button
                                    onClick={() => setShowSettings(false)}
                                    className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium"
                                >
                                    Batal
                                </button>
                                <button
                                    onClick={handleSaveSettings}
                                    className="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700"
                                >
                                    Simpan Pengaturan
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* DRAFTS MODAL */}
                {showDrafts && (
                    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                        <div className="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
                            <div className="p-4 border-b border-gray-100 flex justify-between items-center">
                                <h2 className="text-lg font-bold">📂 Transaksi</h2>
                                <button onClick={() => setShowDrafts(false)} className="text-gray-500 hover:text-gray-700">✕</button>
                            </div>

                            {/* Tabs */}
                            <div className="flex border-b border-gray-200">
                                <button
                                    onClick={() => {
                                        setTransactionTab('draft');
                                        loadDrafts('draft');
                                    }}
                                    className={`flex-1 px-4 py-3 font-medium transition-colors ${transactionTab === 'draft'
                                        ? 'text-primary-600 border-b-2 border-primary-600 bg-primary-50'
                                        : 'text-gray-600 hover:bg-gray-50'
                                        }`}
                                >
                                    📝 Draft
                                </button>
                                <button
                                    onClick={() => {
                                        setTransactionTab('completed');
                                        loadDrafts('completed');
                                    }}
                                    className={`flex-1 px-4 py-3 font-medium transition-colors ${transactionTab === 'completed'
                                        ? 'text-primary-600 border-b-2 border-primary-600 bg-primary-50'
                                        : 'text-gray-600 hover:bg-gray-50'
                                        }`}
                                >
                                    ✅ Completed
                                </button>
                            </div>

                            <div className="flex-1 overflow-y-auto p-4 space-y-3">
                                {drafts.length === 0 ? (
                                    <div className="text-center text-gray-400 py-10">
                                        {transactionTab === 'draft' ? 'Belum ada draft tersimpan' : 'Belum ada transaksi completed'}
                                    </div>
                                ) : (
                                    drafts.map((draft: any) => (
                                        <div key={`${draft.source}-${draft.id}`} className="border border-gray-200 rounded-lg p-4 hover:bg-blue-50 cursor-pointer flex justify-between items-center"
                                            onClick={() => transactionTab === 'draft' && handleResumeDraft(draft)}>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className={`text-xs px-2 py-0.5 rounded font-bold ${draft.source === 'local' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600'}`}>
                                                        {draft.source === 'local' ? '🏠 LOCAL' : '☁️ SERVER'}
                                                    </span>
                                                    <span className="font-bold text-gray-800">{draft.data?.customer_name || 'Tanpa Nama'}</span>
                                                    {draft.data?.invoice_number && (
                                                        <span className="text-xs text-gray-500">#{draft.data.invoice_number}</span>
                                                    )}
                                                </div>
                                                <div className="text-sm text-gray-600 mt-1">
                                                    Rp {draft.data?.total?.toLocaleString('id-ID')}
                                                </div>
                                                <div className="text-xs text-gray-400 mt-1">{new Date(draft.created_at).toLocaleString()}</div>
                                            </div>
                                            <div className="flex gap-2">
                                                {transactionTab === 'draft' && (
                                                    <>
                                                        <button
                                                            onClick={(e) => handleDeleteDraft(draft, e)}
                                                            className="bg-red-100 text-red-600 px-3 py-2 rounded-lg text-sm font-bold hover:bg-red-200"
                                                            title="Hapus Draft"
                                                        >
                                                            🗑️
                                                        </button>
                                                        <button className="bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-primary-700">
                                                            Resume ➡️
                                                        </button>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                )}

                <ProductGrid
                    categories={categories}
                    selectedCategory={selectedCategory}
                    setSelectedCategory={setSelectedCategory}
                    filteredProducts={filteredProducts}
                    addToCart={addToCart}
                />
            </div>

            <CartSidebar
                cart={cart}
                orderNumber={orderNumber}
                customerName={customerName}
                setCustomerName={setCustomerName}
                orderType={orderType}
                setOrderType={setOrderType}
                tableNumber={tableNumber}
                setTableNumber={setTableNumber}
                updateQuantity={updateQuantity}
                updateItemNotes={updateItemNotes}
                discount={discount}
                setDiscount={setDiscount}
                subtotal={cart.reduce((sum, item) => sum + Number(item.subtotal), 0)}
                tax={(cart.reduce((sum, item) => sum + Number(item.subtotal), 0) * (settings?.tax_rate || 0)) / 100}
                total={calculateTotal()}
                taxRate={settings?.tax_rate || 0}
                onCheckout={handleCheckout}
                onSaveDraft={handleSaveDraft}
                onClearCart={() => {
                    setCart([]);
                    setCustomerName('');
                    setTableNumber('');
                    setDiscount(0);
                    setActiveDraft(null);
                }}
                printOrder={printOrder}
                printerSettings={printerSettings}
                settings={settings}
            />

            <ShiftModal
                isOpen={isShiftModalOpen}
                mode={shiftModalMode}
                onClose={() => {
                    if (activeShift) setIsShiftModalOpen(false);
                }}
                onShiftOpened={handleShiftOpened}
                onShiftClosed={handleShiftClosed}
                onPrintReport={handlePrintShiftReport}
                activeShift={activeShift}
            />

            <PaymentModal
                isOpen={isPaymentModalOpen}
                total={calculateTotal()}
                paymentMethods={paymentMethods}
                onConfirm={handlePaymentConfirm}
                onCancel={() => setIsPaymentModalOpen(false)}
            />

            {notification && (
                <Notification
                    message={notification.message}
                    type={notification.type}
                    onClose={() => setNotification(null)}
                />
            )}

            <ConfirmModal
                isOpen={confirmModal.isOpen}
                title={confirmModal.title}
                message={confirmModal.message}
                onConfirm={confirmModal.onConfirm}
                onCancel={closeConfirmModal}
                isDestructive={confirmModal.isDestructive}
            />
        </div>
    );
}

export default App;
