import React, { useState, useEffect, useMemo } from 'react';
import { printerService, type PrinterSettings, DEFAULT_TEMPLATES } from '../services/printer';
import { useTheme } from '../context/ThemeContext';

interface SettingsModalProps {
    isOpen: boolean;
    onClose: () => void;
    printerSettings: PrinterSettings;
    onUpdatePrinterSettings: (settings: PrinterSettings) => void;
    currentApiUrl: string; // To initialize the input
    onSave: (newApiUrl: string) => void;
    showNotification?: (message: string, type: 'success' | 'error' | 'info') => void;
    onResetDatabase?: () => void;
}

const SettingsModal: React.FC<SettingsModalProps> = ({
    isOpen,
    onClose,
    printerSettings,
    onUpdatePrinterSettings,
    currentApiUrl,
    onSave,
    showNotification,
    onResetDatabase
}) => {
    const { theme, toggleTheme } = useTheme();
    const [activeTab, setActiveTab] = useState<'general' | 'printer' | 'template'>('general');
    const [apiUrl, setApiUrl] = useState(currentApiUrl || '');
    const [availablePrinters, setAvailablePrinters] = useState<string[]>([]);
    const [newMapping, setNewMapping] = useState<{ productType: 'raw' | 'produced' | 'retail' | 'bar'; printerName: string; paperWidth: '58mm' | '80mm' }>({ productType: 'produced', printerName: '', paperWidth: '58mm' });
    const [isLoadingPrinters, setIsLoadingPrinters] = useState(false);

    // Template Editor State
    const [templateType, setTemplateType] = useState<'payment' | 'kitchen'>('payment');
    const [templateContent, setTemplateContent] = useState('');

    // Sync local API URL with prop when opened
    useEffect(() => {
        if (isOpen) {
            setApiUrl(currentApiUrl);
            setIsLoadingPrinters(true);
            printerService.getPrinters()
                .then(setAvailablePrinters)
                .finally(() => setIsLoadingPrinters(false));
            // Load initial template
            const initialTemplate = printerSettings.templates?.[templateType] || DEFAULT_TEMPLATES[templateType];
            setTemplateContent(initialTemplate);
        }
    }, [isOpen, currentApiUrl]);

    // When template type changes, load the content
    useEffect(() => {
        if (isOpen) {
            const content = printerSettings.templates?.[templateType] || DEFAULT_TEMPLATES[templateType];
            setTemplateContent(content);
        }
    }, [templateType, printerSettings.templates, isOpen]);

    const handleSaveTemplate = () => {
        const currentTemplates = printerSettings.templates || { ...DEFAULT_TEMPLATES };
        const updatedTemplates = {
            ...currentTemplates,
            [templateType]: templateContent
        };
        onUpdatePrinterSettings({ ...printerSettings, templates: updatedTemplates });
        showNotification?.('✅ Template berhasil disimpan!', 'success');
    };

    const handleResetTemplate = () => {
        if (confirm('Reset template ke default? Perubahan akan hilang.')) {
            setTemplateContent(DEFAULT_TEMPLATES[templateType]);
        }
    };

    // Memoized preview data to avoid recreating on every render
    const previewData = useMemo(() => ({
        store_name: "RESTO LIVE PREVIEW",
        store_address: "Jl. Demo No. 123",
        invoice_number: "INV-001",
        cashier_name: "Budi",
        table_number: "No. 5",
        items: [
            { quantity: 2, product_name: "Nasi Goreng", price: 25000, subtotal: 50000, notes: "Pedas" },
            { quantity: 1, product_name: "Es Teh Manis", price: 5000, subtotal: 5000 }
        ],
        subtotal: 55000,
        tax: 5500,
        tax_rate: 10,
        discount: 0,
        total: 60500,
        is_ticket: templateType === 'kitchen'
    }), [templateType]);

    // Memoized preview HTML to avoid re-rendering on every keystroke
    const previewHtml = useMemo(() =>
        printerService.renderTemplate(templateContent, previewData, '58mm', 'html'),
        [templateContent, previewData]
    );

    const insertPlaceholder = (placeholder: string) => {
        setTemplateContent(prev => prev + placeholder);
        // Ideally insert at cursor, but appending is safer for MVP without ref
    };

    const handleAddMapping = () => {
        if (newMapping.printerName && newMapping.productType) {
            // Check for duplicate productType mapping
            const exists = printerSettings.typeMappings?.some(m => m.productType === newMapping.productType);
            if (exists) {
                showNotification?.('⚠️ Mapping untuk jenis produk ini sudah ada!', 'error');
                return;
            }

            const updatedMappings = [...(printerSettings.typeMappings || []), newMapping];
            onUpdatePrinterSettings({ ...printerSettings, typeMappings: updatedMappings });
            setNewMapping({ productType: 'produced', printerName: '', paperWidth: '58mm' });
        }
    };

    const handleRemoveMapping = (index: number) => {
        const updatedMappings = [...(printerSettings.typeMappings || [])];
        updatedMappings.splice(index, 1);
        onUpdatePrinterSettings({ ...printerSettings, typeMappings: updatedMappings });
    };

    if (!isOpen) return null;

    const PRODUCT_TYPES = [
        { value: 'produced', label: '🍳 Kitchen (Produced)' },
        { value: 'bar', label: '🍹 Bar (Minuman)' },
        { value: 'retail', label: '📦 Retail (Barang Jadi)' },
        { value: 'raw', label: '🥦 Bahan Baku' },
        // { value: 'service', label: 'Service' }
    ];

    return (
        <div className="fixed inset-0 bg-gray-900/40 dark:bg-black/60 z-[60] flex backdrop-blur-sm items-center justify-center p-4 animate-fade-in" onClick={onClose}>
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-6xl p-0 overflow-hidden flex flex-col max-h-[90vh] animate-scale-up border border-gray-100 dark:border-gray-700" onClick={e => e.stopPropagation()}>
                <div className="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                    <h2 className="text-lg font-bold text-gray-800 dark:text-white">⚙️ Pengaturan</h2>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">✕</button>
                </div>

                <div className="flex border-b border-gray-200 dark:border-gray-700">
                    <button
                        onClick={() => setActiveTab('general')}
                        className={`flex-1 py-3 text-sm font-medium transition-colors ${activeTab === 'general' ? 'border-b-2 border-primary-600 text-primary-600 bg-white dark:bg-gray-800 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 bg-gray-50 dark:bg-gray-900'}`}
                    >
                        Umum
                    </button>
                    <button
                        onClick={() => setActiveTab('printer')}
                        className={`flex-1 py-3 text-sm font-medium transition-colors ${activeTab === 'printer' ? 'border-b-2 border-primary-600 text-primary-600 bg-white dark:bg-gray-800 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 bg-gray-50 dark:bg-gray-900'}`}
                    >
                        Printer & Struk
                    </button>
                    <button
                        onClick={() => setActiveTab('template')}
                        className={`flex-1 py-3 text-sm font-medium transition-colors ${activeTab === 'template' ? 'border-b-2 border-primary-600 text-primary-600 bg-white dark:bg-gray-800 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 bg-gray-50 dark:bg-gray-900'}`}
                    >
                        Edit Template
                    </button>
                </div>

                <div className="p-6 overflow-y-auto">
                    {activeTab === 'general' ? (
                        <div className="space-y-6">
                            {/* Theme Toggle */}
                            <div className="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg border border-indigo-100 dark:border-indigo-800">
                                <h3 className="text-sm font-bold text-gray-800 dark:text-white mb-2">Tampilan</h3>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-700 dark:text-gray-300">Mode Gelap</p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">Aktifkan tema gelap untuk kenyamanan mata.</p>
                                    </div>
                                    <button
                                        onClick={toggleTheme}
                                        className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${theme === 'dark' ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'}`}
                                    >
                                        <span
                                            aria-hidden="true"
                                            className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${theme === 'dark' ? 'translate-x-5' : 'translate-x-0'}`}
                                        />
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Server API URL</label>
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        className="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 dark:text-white"
                                        value={apiUrl}
                                        onChange={(e) => setApiUrl(e.target.value)}
                                        placeholder="http://localhost:8000/api"
                                    />
                                    <button
                                        onClick={async () => {
                                            if (!apiUrl) {
                                                showNotification?.('⚠️ URL API belum diisi!', 'error');
                                                return;
                                            }
                                            // Strip trailing slash
                                            const cleanUrl = (apiUrl || '').replace(/\/$/, '');
                                            console.log(`Testing connection to: ${cleanUrl}/pos/status`);
                                            try {
                                                // Use vanilla fetch for test to bypass potential axios interceptor issues
                                                const response = await fetch(`${cleanUrl}/pos/status`, {
                                                    method: 'GET',
                                                    headers: {
                                                        'Accept': 'application/json'
                                                    }
                                                });

                                                if (!response.ok) {
                                                    throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
                                                }

                                                const data = await response.json();
                                                console.log('Test success:', data);
                                                showNotification?.(`✅ Terhubung! Server: ${data.message}`, 'success');
                                            } catch (e: any) {
                                                console.error('Test failed:', e);
                                                showNotification?.(`❌ Gagal terhubung: ${e.message || 'Unknown Error'}`, 'error');
                                            }
                                        }}
                                        className="px-3 py-2 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50"
                                    >
                                        📡 Tes
                                    </button>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Pastikan URL diakhiri dengan <code>/api</code></p>
                            </div>

                            {/* Danger Zone - Reset Database */}
                            <div className="pt-6 mt-6 border-t border-red-200 dark:border-red-900">
                                <h3 className="text-sm font-semibold text-red-600 dark:text-red-400 mb-3">⚠️ Danger Zone</h3>
                                <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                    <p className="text-sm text-red-700 dark:text-red-300 mb-3">
                                        Reset akan menghapus semua data lokal (produk, transaksi, shift) dan kembali ke setup wizard.
                                    </p>
                                    <button
                                        onClick={() => onResetDatabase?.()}
                                        className="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2"
                                    >
                                        <span>🔄</span>
                                        <span>Reset Database & Setup</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : activeTab === 'printer' ? (
                        <div className="space-y-6">
                            {/* Cashier Printer */}
                            <div className="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                                <label className="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Printer Kasir (Utama)</label>
                                <div className="flex gap-2 mb-2">
                                    <select
                                        className="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 dark:text-white"
                                        value={printerSettings.cashierPrinter}
                                        onChange={(e) => onUpdatePrinterSettings({ ...printerSettings, cashierPrinter: e.target.value })}
                                    >
                                        <option value="">-- Pilih Printer --</option>
                                        {availablePrinters.map(p => <option key={p} value={p}>{p}</option>)}
                                    </select>
                                    {isLoadingPrinters && (
                                        <span className="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 px-2">
                                            <svg className="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Loading...
                                        </span>
                                    )}
                                    <select
                                        className="w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 dark:text-white"
                                        value={printerSettings.cashierPaperWidth || '58mm'}
                                        onChange={(e) => onUpdatePrinterSettings({ ...printerSettings, cashierPaperWidth: e.target.value as '58mm' | '80mm' })}
                                    >
                                        <option value="58mm">58mm</option>
                                        <option value="80mm">80mm</option>
                                    </select>
                                    <button
                                        onClick={async () => {
                                            if (!printerSettings.cashierPrinter) {
                                                showNotification?.('⚠️ Pilih printer terlebih dahulu', 'error');
                                                return;
                                            }
                                            try {
                                                showNotification?.('🖨️ Mengirim test print...', 'info');
                                                const testText = `TEST PRINTER\n--------------------------------\nPrinter: ${printerSettings.cashierPrinter}\nWaktu: ${new Date().toLocaleString()}\n--------------------------------\nLebar: ${printerSettings.cpl || (printerSettings.cashierPaperWidth === '80mm' ? 48 : 32)} Karakter\nKiri                       Kanan\nTENGAH\n--------------------------------\nTerima Kasih\n\n\n`;
                                                await printerService.printJob(printerSettings.cashierPrinter, testText);
                                                showNotification?.('✅ Berhasil dikirim ke spooler!', 'success');
                                            } catch (e: any) {
                                                showNotification?.(`❌ Gagal: ${e.message || e}`, 'error');
                                            }
                                        }}
                                        disabled={!printerSettings.cashierPrinter}
                                        className="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 font-medium text-sm"
                                        title="Test Print"
                                    >
                                        🖨️ Test
                                    </button>
                                </div>

                                <div className="mb-4 bg-yellow-50 dark:bg-yellow-900/10 p-2 rounded border border-yellow-200 dark:border-yellow-800">
                                    <div className="flex items-center gap-2">
                                        <label className="text-xs font-bold text-yellow-800 dark:text-yellow-200 whitespace-nowrap">Kustom Lebar (CPL):</label>
                                        <input
                                            type="number"
                                            placeholder={printerSettings.cashierPaperWidth === '80mm' ? "48" : "32"}
                                            className="w-20 px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 dark:text-gray-200"
                                            value={printerSettings.cpl || ''}
                                            onChange={(e) => onUpdatePrinterSettings({ ...printerSettings, cpl: e.target.value ? parseInt(e.target.value) : undefined })}
                                        />
                                        <span className="text-xs text-yellow-700 dark:text-yellow-300">Karakter per baris</span>
                                    </div>
                                    <p className="text-[10px] text-yellow-700 dark:text-yellow-400 mt-1">
                                        Atur manual jika hasil print terlalu kecil/lebar. (Standar 58mm=32, 80mm=48/64).
                                    </p>
                                </div>

                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="autoPrint"
                                        checked={printerSettings.autoPrint}
                                        onChange={(e) => onUpdatePrinterSettings({ ...printerSettings, autoPrint: e.target.checked })}
                                        className="rounded text-primary-600 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600"
                                    />
                                    <label htmlFor="autoPrint" className="text-sm font-medium text-gray-700 dark:text-gray-300">Otomatis Cetak (Direct Print)</label>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">Jika aktif, struk langsung dicetak tanpa dialog Windows.</p>
                            </div>

                            <hr className="my-2 border-gray-200 dark:border-gray-700" />

                            {/* Type Mappings */}
                            <div>
                                <h3 className="text-sm font-bold text-gray-800 dark:text-white mb-2">🔀 Printer Per Divisi (Dapur/Bar/dll)</h3>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-3">Item dengan jenis produk ini akan dicetak terpisah ke printer yang dipilih (Tiket Pesanan).</p>

                                <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm text-left min-w-[300px]">
                                            <thead className="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-medium">
                                                <tr>
                                                    <th className="px-3 py-2">Jenis Barang</th>
                                                    <th className="px-3 py-2">Target Printer</th>
                                                    <th className="px-3 py-2">Size</th>
                                                    <th className="px-3 py-2 w-10"></th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                                {printerSettings.typeMappings && printerSettings.typeMappings.map((mapping, idx) => {
                                                    const typeLabel = PRODUCT_TYPES.find(t => t.value === mapping.productType)?.label || mapping.productType;
                                                    return (
                                                        <tr key={idx} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                            <td className="px-3 py-2 dark:text-gray-200">{typeLabel}</td>
                                                            <td className="px-3 py-2 font-mono text-xs dark:text-gray-300">{mapping.printerName}</td>
                                                            <td className="px-3 py-2 font-mono text-xs dark:text-gray-300">{mapping.paperWidth || '58mm'}</td>
                                                            <td className="px-3 py-2 text-center">
                                                                <button
                                                                    onClick={() => handleRemoveMapping(idx)}
                                                                    className="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-bold"
                                                                    title="Hapus Mapping"
                                                                >
                                                                    ✕
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                                {(!printerSettings.typeMappings || printerSettings.typeMappings.length === 0) && (
                                                    <tr>
                                                        <td colSpan={4} className="px-3 py-4 text-center text-gray-400 dark:text-gray-500 italic">Belum ada mapping printer devisi.</td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Add New Mapping Form */}
                                    <div className="bg-gray-50 dark:bg-gray-900 p-3 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                                        <select
                                            className="flex-1 px-2 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 dark:text-white"
                                            value={newMapping.productType}
                                            onChange={(e) => setNewMapping({ ...newMapping, productType: e.target.value as any })}
                                        >
                                            {PRODUCT_TYPES.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
                                        </select>

                                        <span className="text-gray-400 dark:text-gray-600 text-center hidden sm:block">➜</span>
                                        <span className="text-gray-400 dark:text-gray-600 text-center block sm:hidden">⬇</span>

                                        <select
                                            className="flex-1 px-2 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 dark:text-white"
                                            value={newMapping.printerName}
                                            onChange={(e) => setNewMapping({ ...newMapping, printerName: e.target.value })}
                                        >
                                            <option value="">Pilih Printer...</option>
                                            {availablePrinters.map(p => <option key={p} value={p}>{p}</option>)}
                                        </select>

                                        <div className="flex gap-2">
                                            <select
                                                className="w-full sm:w-20 px-2 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 dark:text-white"
                                                value={newMapping.paperWidth}
                                                onChange={(e) => setNewMapping({ ...newMapping, paperWidth: e.target.value as '58mm' | '80mm' })}
                                            >
                                                <option value="58mm">58mm</option>
                                                <option value="80mm">80mm</option>
                                            </select>
                                            <button
                                                onClick={handleAddMapping}
                                                disabled={!newMapping.printerName || !newMapping.productType}
                                                className="flex-1 sm:flex-none bg-green-600 text-white px-3 py-2 rounded text-sm font-bold hover:bg-green-700 disabled:bg-gray-300 disabled:dark:bg-gray-700 disabled:cursor-not-allowed whitespace-nowrap"
                                            >
                                                + Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-4 animate-fade-in h-full flex flex-col">
                            <div className="flex justify-between items-center">
                                <div className="flex gap-2 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                                    <button
                                        onClick={() => setTemplateType('payment')}
                                        className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${templateType === 'payment' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'}`}
                                    >
                                        🧾 Struk Pembayaran
                                    </button>
                                    <button
                                        onClick={() => setTemplateType('kitchen')}
                                        className={`px-3 py-1.5 rounded-md text-sm font-medium transition-colors ${templateType === 'kitchen' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'}`}
                                    >
                                        🍳 Tiket Dapur
                                    </button>
                                </div>
                                <div className="flex gap-2">
                                    <button onClick={handleResetTemplate} className="text-xs text-red-500 hover:text-red-700 underline">Reset Default</button>
                                </div>
                            </div>

                            <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4 min-h-[500px]">
                                <div className="flex flex-col gap-2 h-full">
                                    <label className="text-sm font-bold text-gray-700 dark:text-gray-300">Editor Template</label>
                                    <div className="flex flex-wrap gap-1 mb-2">
                                        <span className="text-xs text-gray-500 w-full mb-0.5">Placeholder Data:</span>
                                        {['{{store_name}}', '{{date}}', '{{items}}', '{{total}}', '{{line}}', '{{footer}}'].map(tag => (
                                            <button key={tag} onClick={() => insertPlaceholder(tag)} className="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600 font-mono text-blue-600 dark:text-blue-400">
                                                {tag}
                                            </button>
                                        ))}
                                    </div>
                                    <div className="flex flex-wrap gap-1 mb-2">
                                        <span className="text-xs text-gray-500 w-full mb-0.5">Format Alignment:</span>
                                        <button onClick={() => insertPlaceholder('{{c: teks}}')} className="px-2 py-1 bg-purple-50 dark:bg-purple-900/30 text-xs rounded border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-800 font-mono text-purple-600 dark:text-purple-400" title="Rata Tengah">
                                            Center
                                        </button>
                                        <button onClick={() => insertPlaceholder('{{r: teks}}')} className="px-2 py-1 bg-purple-50 dark:bg-purple-900/30 text-xs rounded border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-800 font-mono text-purple-600 dark:text-purple-400" title="Rata Kanan">
                                            Right
                                        </button>
                                        <button onClick={() => insertPlaceholder('{{lr: Kiri | Kanan}}')} className="px-2 py-1 bg-purple-50 dark:bg-purple-900/30 text-xs rounded border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-800 font-mono text-purple-600 dark:text-purple-400" title="Kiri | Kanan">
                                            Left | Right
                                        </button>
                                    </div>
                                    <div className="flex flex-wrap gap-1 mb-2">
                                        <span className="text-xs text-gray-500 w-full mb-0.5">Format Styling:</span>
                                        <button onClick={() => insertPlaceholder('{{b: teks}}')} className="px-2 py-1 bg-rose-50 dark:bg-rose-900/30 text-xs rounded border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-800 font-mono font-bold text-rose-600 dark:text-rose-400" title="Tebal">
                                            Bold
                                        </button>
                                        <button onClick={() => insertPlaceholder('{{size:2: teks}}')} className="px-2 py-1 bg-rose-50 dark:bg-rose-900/30 text-xs rounded border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-800 font-mono text-rose-600 dark:text-rose-400" title="Ukuran Besar">
                                            Large (2x)
                                        </button>
                                    </div>
                                    <textarea
                                        className="flex-1 w-full p-3 font-mono text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-primary-500 resize-none"
                                        value={templateContent}
                                        onChange={(e) => setTemplateContent(e.target.value)}
                                        spellCheck={false}
                                    />
                                </div>
                                <div className="flex flex-col gap-2 h-full">
                                    <label className="text-sm font-bold text-gray-700 dark:text-gray-300">Preview (Simulasi)</label>
                                    <div className="flex-1 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 overflow-auto shadow-inner">
                                        <div
                                            className="font-mono text-[11px] leading-tight text-black dark:text-gray-300 whitespace-pre-wrap select-none"
                                            dangerouslySetInnerHTML={{ __html: previewHtml }}
                                        />
                                    </div>
                                    <p className="text-xs text-gray-400 text-center">Preview Bold & Large (Mode HTML)</p>
                                </div>
                            </div>

                            <div className="flex justify-between items-center bg-yellow-50 dark:bg-yellow-900/10 p-2 rounded border border-yellow-200 dark:border-yellow-800 mt-auto">
                                <p className="text-xs text-yellow-800 dark:text-yellow-200">ℹ️ Pastikan tag <code>{`{{items}}`}</code> ada untuk menampilkan daftar pesanan.</p>
                                <button onClick={handleSaveTemplate} className="px-3 py-1 bg-primary-600 text-white text-xs rounded hover:bg-primary-700 font-bold">
                                    Simpan Template Ini
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                <div className="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2 bg-gray-50 dark:bg-gray-900">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg font-medium transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        onClick={() => onSave(apiUrl)}
                        className="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700"
                    >
                        Simpan Pengaturan
                    </button>
                </div>
            </div>
        </div>
    );
};

export default SettingsModal;
