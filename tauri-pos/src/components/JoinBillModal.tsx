
import React, { useState, useEffect } from 'react';


interface JoinBillModalProps {
    isOpen: boolean;
    drafts: any[]; // List of available drafts
    onClose: () => void;
    onMerge: (selectedDraftIds: number[]) => void;
}

const JoinBillModal: React.FC<JoinBillModalProps> = ({ isOpen, drafts, onClose, onMerge }) => {
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    useEffect(() => {
        if (!isOpen) {
            setSelectedIds([]);
        }
    }, [isOpen]);

    const toggleSelection = (id: number) => {
        if (selectedIds.includes(id)) {
            setSelectedIds(selectedIds.filter(sid => sid !== id));
        } else {
            setSelectedIds([...selectedIds, id]);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-gray-900/40 dark:bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 animate-fade-in">
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh] animate-scale-up border border-gray-100 dark:border-gray-700">
                <div className="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900">
                    <h2 className="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>🔗</span> Gabung Tagihan (Join Bill)
                    </h2>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 font-bold text-2xl">&times;</button>
                </div>

                <div className="p-4 overflow-y-auto flex-1">
                    <p className="text-gray-500 dark:text-gray-400 mb-4 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg text-sm border-l-4 border-blue-500 dark:border-blue-400">
                        Pilih minimal 2 transaksi yang ingin digabungkan. Tagihan baru akan dibuat berisi gabungan item dari transaksi yang dipilih.
                        Transaksi lama akan dihapus.
                    </p>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {drafts.map((draft) => {
                            const isSelected = selectedIds.includes(draft.id);
                            const data = draft.data || {};

                            return (
                                <div
                                    key={draft.id}
                                    onClick={() => toggleSelection(draft.id)}
                                    className={`
                                        border-2 rounded-xl p-3 cursor-pointer transition-all relative
                                        ${isSelected
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 ring-2 ring-primary-200 dark:ring-primary-900'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/50 bg-white dark:bg-gray-800'}
                                    `}
                                >
                                    {isSelected && (
                                        <div className="absolute top-2 right-2 text-primary-600 dark:text-primary-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                    )}

                                    <div className="flex justify-between items-start mb-2">
                                        <div className={`text-xs px-2 py-0.5 rounded font-bold ${draft.source === 'local' ? 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'}`}>
                                            {draft.source === 'local' ? '🏠 LOCAL' : '☁️ SERVER'}
                                        </div>
                                        <div className="text-xs text-gray-400">{new Date(draft.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                                    </div>

                                    <h4 className="font-bold text-gray-800 dark:text-gray-100 truncate mb-1">{data.customer_name || 'Tanpa Nama'}</h4>

                                    <div className="flex justify-between items-center text-sm">
                                        <span className="text-gray-500 dark:text-gray-400">{data.items?.length || 0} Item</span>
                                        <span className="font-bold text-gray-900 dark:text-white">Rp {data.total?.toLocaleString('id-ID')}</span>
                                    </div>

                                    {data.table_number && (
                                        <div className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            Meja: <span className="font-semibold text-gray-700 dark:text-gray-300">{data.table_number}</span>
                                        </div>
                                    )}
                                </div>
                            );
                        })}

                        {drafts.length === 0 && (
                            <div className="col-span-2 text-center py-8 text-gray-400 dark:text-gray-500">
                                Tidak ada draft yang tersedia.
                            </div>
                        )}
                    </div>
                </div>

                <div className="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
                    <button
                        onClick={onClose}
                        className="px-6 py-2.5 rounded-lg text-gray-700 dark:text-gray-300 font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        onClick={() => onMerge(selectedIds)}
                        disabled={selectedIds.length < 2}
                        className="px-6 py-2.5 rounded-lg bg-primary-600 text-white font-bold hover:bg-primary-700 disabled:bg-gray-300 disabled:dark:bg-gray-700 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl active:scale-95 flex items-center gap-2"
                    >
                        <span>🔗</span> Gabung ({selectedIds.length})
                    </button>
                </div>
            </div>
        </div>
    );
};

export default JoinBillModal;
