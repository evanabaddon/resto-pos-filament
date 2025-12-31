import React, { useState, useEffect } from 'react';
import { api } from '../services/api';
import { printerService, type PrinterSettings } from '../services/printer';
import type { Category } from '../types';

interface SettingsModalProps {
    isOpen: boolean;
    onClose: () => void;
    printerSettings: PrinterSettings;
    onUpdatePrinterSettings: (settings: PrinterSettings) => void;
    categories: Category[];
    currentApiUrl: string; // To initialize the input
    onSave: (newApiUrl: string) => void;
}

const SettingsModal: React.FC<SettingsModalProps> = ({
    isOpen,
    onClose,
    printerSettings,
    onUpdatePrinterSettings,
    categories,
    currentApiUrl,
    onSave
}) => {
    const [activeTab, setActiveTab] = useState<'general' | 'printer'>('general');
    const [apiUrl, setApiUrl] = useState(currentApiUrl);
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
        <div className="fixed inset-0 bg-black/50 z-[60] flex backdrop-blur-sm items-center justify-center p-4">
            <div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-0 overflow-hidden flex flex-col max-h-[90vh]">
                <div className="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 className="text-lg font-bold">⚙️ Pengaturan</h2>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-700">✕</button>
                </div>

                <div className="flex border-b border-gray-200">
                    <button
                        onClick={() => setActiveTab('general')}
                        className={`flex-1 py-3 text-sm font-medium transition-colors ${activeTab === 'general' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-700 bg-gray-50'}`}
                    >
                        Umum
                    </button>
                    <button
                        onClick={() => setActiveTab('printer')}
                        className={`flex-1 py-3 text-sm font-medium transition-colors ${activeTab === 'printer' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-500 hover:text-gray-700 bg-gray-50'}`}
                    >
                        Printer & Struk
                    </button>
                </div>

                <div className="p-6 overflow-y-auto">
                    {activeTab === 'general' ? (
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Server API URL</label>
                                <div className="flex gap-2">
                                    <input
                                        type="text"
                                        className="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-500"
                                        value={apiUrl}
                                        onChange={(e) => setApiUrl(e.target.value)}
                                        placeholder="http://localhost:8000/api"
                                    />
                                    <button
                                        onClick={async () => {
                                            try {
                                                const res = await api.testConnection(apiUrl);
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
                                        onChange={(e) => onUpdatePrinterSettings({ ...printerSettings, cashierPrinter: e.target.value })}
                                    >
                                        <option value="">-- Pilih Printer --</option>
                                        {availablePrinters.map(p => <option key={p} value={p}>{p}</option>)}
                                    </select>
                                    <select
                                        className="w-24 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-primary-500"
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
                                                                    onUpdatePrinterSettings({ ...printerSettings, categoryMappings: newMappings });
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
                        onClick={onClose}
                        className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg font-medium"
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
