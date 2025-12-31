import React from 'react';
import type { OrderDraft } from '../types';

interface DraftsModalProps {
    isOpen: boolean;
    onClose: () => void;
    activeTab: 'draft' | 'completed';
    onTabChange: (tab: 'draft' | 'completed') => void;
    drafts: OrderDraft[];
    isLoading: boolean;
    onDelete: (draft: OrderDraft, e: React.MouseEvent) => void;
    onResume: (draft: OrderDraft) => void;
    onReprint: (draft: OrderDraft, e: React.MouseEvent) => void;
    onOpenJoin: () => void;
}

const DraftsModal: React.FC<DraftsModalProps> = ({
    isOpen,
    onClose,
    activeTab,
    onTabChange,
    drafts,
    isLoading,
    onDelete,
    onResume,
    onReprint,
    onOpenJoin
}) => {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/50 z-50 flex backdrop-blur-sm items-center justify-center p-4">
            <div className="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
                <div className="p-4 border-b border-gray-100 flex justify-between items-center">
                    <div className="flex items-center gap-3">
                        <h2 className="text-lg font-bold">📂 Transaksi</h2>
                        {activeTab === 'draft' && drafts.length > 1 && (
                            <button
                                onClick={onOpenJoin}
                                className="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg font-bold shadow-sm flex items-center gap-1 text-xs"
                            >
                                <span>🔗</span> Gabung
                            </button>
                        )}
                    </div>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-700">✕</button>
                </div>

                {/* Tabs */}
                <div className="flex border-b border-gray-200">
                    <button
                        onClick={() => onTabChange('draft')}
                        className={`flex-1 px-4 py-3 font-medium transition-colors ${activeTab === 'draft'
                            ? 'text-primary-600 border-b-2 border-primary-600 bg-primary-50'
                            : 'text-gray-600 hover:bg-gray-50'
                            }`}
                    >
                        📝 Draft
                    </button>
                    <button
                        onClick={() => onTabChange('completed')}
                        className={`flex-1 px-4 py-3 font-medium transition-colors ${activeTab === 'completed'
                            ? 'text-primary-600 border-b-2 border-primary-600 bg-primary-50'
                            : 'text-gray-600 hover:bg-gray-50'
                            }`}
                    >
                        ✅ Completed
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto p-4 space-y-3">
                    {isLoading ? (
                        <div className="flex flex-col items-center justify-center py-10 space-y-2 text-gray-400">
                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
                            <p className="text-sm">Memuat data...</p>
                        </div>
                    ) : drafts.length === 0 ? (
                        <div className="text-center text-gray-400 py-10">
                            {activeTab === 'draft' ? 'Belum ada draft tersimpan' : 'Belum ada transaksi completed'}
                        </div>
                    ) : (
                        drafts.map((draft) => (
                            <div key={`${draft.source}-${draft.id}`} className="border border-gray-200 rounded-lg p-4 hover:bg-blue-50 cursor-pointer flex justify-between items-center"
                                onClick={() => activeTab === 'draft' && onResume(draft)}>
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
                                    {activeTab === 'draft' && (
                                        <>
                                            <button
                                                onClick={(e) => onDelete(draft, e)}
                                                className="bg-red-100 text-red-600 px-3 py-2 rounded-lg text-sm font-bold hover:bg-red-200"
                                                title="Hapus Draft"
                                            >
                                                🗑️
                                            </button>
                                            <button
                                                onClick={(e) => onReprint(draft, e)}
                                                className="bg-gray-100 text-gray-600 px-3 py-2 rounded-lg text-sm font-bold hover:bg-gray-200"
                                                title="Cetak Ulang"
                                            >
                                                🖨️
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
    );
};

export default DraftsModal;
