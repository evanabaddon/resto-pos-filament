import React, { useState, useEffect } from 'react';
import { printerService, type PrinterSettings } from '../services/printer';
import { useTheme } from '../context/ThemeContext';
import type { Category } from '../types';

interface SettingsModalProps {
    isOpen: boolean;
    onClose: () => void;
    printerSettings: PrinterSettings;
    onUpdatePrinterSettings: (settings: PrinterSettings) => void;
    categories: Category[];
    currentApiUrl: string; // To initialize the input
    onSave: (newApiUrl: string) => void;
    showNotification?: (message: string, type: 'success' | 'error' | 'info') => void;
}

const SettingsModal: React.FC<SettingsModalProps> = ({
    isOpen,
    onClose,
    printerSettings,
    onUpdatePrinterSettings,
    categories,
    currentApiUrl,
    onSave,
    showNotification
}) => {
    const { theme, toggleTheme } = useTheme();
    const [activeTab, setActiveTab] = useState<'general' | 'printer'>('general');
    const [apiUrl, setApiUrl] = useState(currentApiUrl || '');
    const [availablePrinters, setAvailablePrinters] = useState<string[]>([]);
    const [newMapping, setNewMapping] = useState<{ categoryId: string, printerName: string, paperWidth: '58mm' | '80mm' }>({ categoryId: '', printerName: '', paperWidth: '58mm' });

    // Sync local API URL with prop when opened
    useEffect(() => {
        if (isOpen) {
            setApiUrl(currentApiUrl);
            printerService.getPrinters().then(setAvailablePrinters);
        }
    }, [isOpen, currentApiUrl]);

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-gray-900/40 dark:bg-black/60 z-[60] flex backdrop-blur-sm items-center justify-center p-4 animate-fade-in">
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg p-0 overflow-hidden flex flex-col max-h-[90vh] animate-scale-up border border-gray-100 dark:border-gray-700">
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
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {/* Main Cashier Printer */}
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
                                    <select
                                        className="w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-primary-500 bg-white dark:bg-gray-700 dark:text-white"
                                        value={printerSettings.cashierPaperWidth || '58mm'}
                                        onChange={(e) => onUpdatePrinterSettings({ ...printerSettings, cashierPaperWidth: e.target.value as '58mm' | '80mm' })}
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
                                        onChange={(e) => onUpdatePrinterSettings({ ...printerSettings, autoPrint: e.target.checked })}
                                        className="rounded text-primary-600 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600"
                                    />
                                    <label htmlFor="autoPrint" className="text-sm font-medium text-gray-700 dark:text-gray-300">Otomatis Cetak (Direct Print)</label>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">Jika aktif, struk langsung dicetak tanpa dialog Windows.</p>
                            </div>

                            <hr className="my-2 border-gray-200 dark:border-gray-700" />

                            {/* Category Mappings */}
                            <div>
                                <h3 className="text-sm font-bold text-gray-800 dark:text-white mb-2">🔀 Mapping Printer Kategori (Dapur/Bar/dll)</h3>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-3">Item dalam kategori ini akan dicetak terpisah ke printer yang dipilih (Tiket Pesanan).</p>

                                <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <table className="w-full text-sm text-left">
                                        <thead className="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-medium">
                                            <tr>
                                                <th className="px-3 py-2">Kategori</th>
                                                <th className="px-3 py-2">Target Printer</th>
                                                <th className="px-3 py-2">Size</th>
                                                <th className="px-3 py-2 w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                            {printerSettings.categoryMappings && printerSettings.categoryMappings.map((mapping, idx) => {
                                                const catName = categories.find(c => c.id === mapping.categoryId)?.name || `ID: ${mapping.categoryId}`;
                                                return (
                                                    <tr key={idx} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                        <td className="px-3 py-2 dark:text-gray-200">{catName}</td>
                                                        <td className="px-3 py-2 font-mono text-xs dark:text-gray-300">{mapping.printerName}</td>
                                                        <td className="px-3 py-2 font-mono text-xs dark:text-gray-300">{mapping.paperWidth || '58mm'}</td>
                                                        <td className="px-3 py-2 text-center">
                                                            <button
                                                                onClick={() => {
                                                                    const newMappings = [...printerSettings.categoryMappings];
                                                                    newMappings.splice(idx, 1);
                                                                    onUpdatePrinterSettings({ ...printerSettings, categoryMappings: newMappings });
                                                                }}
                                                                className="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-bold"
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
                                                    <td colSpan={4} className="px-3 py-4 text-center text-gray-400 dark:text-gray-500 italic">Belum ada mapping printer.</td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>

                                    {/* Add New Mapping Form */}
                                    <div className="bg-gray-50 dark:bg-gray-900 p-3 border-t border-gray-200 dark:border-gray-700 flex gap-2 items-center">
                                        <select
                                            className="flex-1 px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 dark:text-white"
                                            value={newMapping.categoryId}
                                            onChange={(e) => setNewMapping({ ...newMapping, categoryId: e.target.value })}
                                        >
                                            <option value="">Pilih Kategori...</option>
                                            {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                        </select>
                                        <span className="text-gray-400 dark:text-gray-600">➜</span>
                                        <select
                                            className="flex-1 px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 dark:text-white"
                                            value={newMapping.printerName}
                                            onChange={(e) => setNewMapping({ ...newMapping, printerName: e.target.value })}
                                        >
                                            <option value="">Pilih Printer...</option>
                                            {availablePrinters.map(p => <option key={p} value={p}>{p}</option>)}
                                        </select>
                                        <select
                                            className="w-20 px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 dark:text-white"
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
                                                    showNotification?.('⚠️ Kategori ini sudah memiliki mapping!', 'error');
                                                    return;
                                                }
                                                onUpdatePrinterSettings({
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
                                            className="bg-green-600 text-white px-3 py-1.5 rounded text-sm font-bold hover:bg-green-700 disabled:bg-gray-300 disabled:dark:bg-gray-700 disabled:cursor-not-allowed"
                                        >
                                            + Tambah
                                        </button>
                                    </div>
                                </div>
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
