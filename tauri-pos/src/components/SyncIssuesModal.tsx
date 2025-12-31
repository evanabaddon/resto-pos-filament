import React, { useState, useEffect } from 'react';
import { dbService } from '../services/db';
import { syncService } from '../services/sync';

interface SyncIssuesModalProps {
    isOpen: boolean;
    onClose: () => void;
    onIssuesResolved: () => void; // Trigger reload of pending count/issues
}

export const SyncIssuesModal: React.FC<SyncIssuesModalProps> = ({ isOpen, onClose, onIssuesResolved }) => {
    const [issues, setIssues] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);

    const loadIssues = async () => {
        setLoading(true);
        try {
            const data = await dbService.getSyncIssues();
            setIssues(data);
        } catch (error) {
            console.error('Failed to load sync issues:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (isOpen) {
            loadIssues();
        }
    }, [isOpen]);

    const handleRetry = async (localId: number) => {
        await dbService.retrySale(localId);
        await loadIssues();
        onIssuesResolved();
    };

    const handleDelete = async (localId: number) => {
        if (confirm('Apakah Anda yakin ingin menghapus data ini secara permanen? Stok akan dikembalikan.')) {
            await dbService.deleteSale(localId);
            await loadIssues();
            onIssuesResolved();
        }
    };

    const handleRetryAll = async () => {
        setLoading(true);
        for (const issue of issues) {
            await dbService.retrySale(issue.local_id);
        }
        await syncService.syncSales(); // Try syncing immediately
        await loadIssues();
        onIssuesResolved();
        setLoading(false);
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
                <div className="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-red-50 dark:bg-red-900/20">
                    <div>
                        <h2 className="text-xl font-bold text-red-700 dark:text-red-400">Masalah Sinkronisasi ({issues.length})</h2>
                        <p className="text-sm text-red-600 dark:text-red-300 mt-1">Transaksi ini gagal diupload ke server.</p>
                    </div>
                    <button onClick={onClose} className="p-2 hover:bg-red-200 dark:hover:bg-red-900/40 rounded-full transition-colors text-red-700 dark:text-red-400">
                        ✕
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto p-6 space-y-4">
                    {loading ? (
                        <div className="text-center py-8 text-gray-500 dark:text-gray-400">Memuat data...</div>
                    ) : issues.length === 0 ? (
                        <div className="text-center py-12 flex flex-col items-center">
                            <span className="text-4xl mb-3">✅</span>
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">Semua Masalah Teratasi</h3>
                            <p className="text-gray-500 dark:text-gray-400">Tidak ada transaksi yang gagal sinkron.</p>
                        </div>
                    ) : (
                        issues.map((issue) => {
                            const data = JSON.parse(issue.sale_data);
                            return (
                                <div key={issue.local_id} className="bg-white dark:bg-gray-800 border border-red-200 dark:border-red-900 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
                                    <div className="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 className="font-bold text-gray-900 dark:text-white">
                                                Order #{data.invoice_number || issue.local_id}
                                            </h4>
                                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {new Date(issue.created_at).toLocaleString('id-ID')}
                                            </p>
                                        </div>
                                        <span className="px-2 py-1 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 text-xs font-bold rounded-lg uppercase tracking-wider">
                                            Gagal
                                        </span>
                                    </div>

                                    <div className="bg-red-50 dark:bg-red-900/10 p-3 rounded-lg border border-red-100 dark:border-red-800 mb-4">
                                        <p className="text-sm text-red-800 dark:text-red-300 font-mono break-all">
                                            {issue.error_message || 'Unknown Error'}
                                        </p>
                                    </div>

                                    <div className="flex justify-end gap-3 pt-2 border-t border-gray-50 dark:border-gray-700">
                                        <button
                                            onClick={() => handleDelete(issue.local_id)}
                                            className="px-3 py-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-lg text-sm font-medium transition-colors"
                                        >
                                            Hapus
                                        </button>
                                        <button
                                            onClick={() => handleRetry(issue.local_id)}
                                            className="px-3 py-1.5 bg-gray-900 dark:bg-gray-700 text-white rounded-lg text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-600 transition-colors shadow-sm"
                                        >
                                            Coba Lagi
                                        </button>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>

                {issues.length > 0 && (
                    <div className="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end">
                        <button
                            onClick={handleRetryAll}
                            disabled={loading}
                            className="px-4 py-2 bg-primary-600 text-white rounded-xl font-bold hover:bg-primary-700 shadow-lg shadow-primary-600/20 active:scale-95 transition-all text-sm w-full sm:w-auto"
                        >
                            {loading ? 'Memproses...' : 'Coba Lagi Semua'}
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
};
