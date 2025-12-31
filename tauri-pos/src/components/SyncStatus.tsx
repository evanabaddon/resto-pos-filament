
import React from 'react';

interface SyncStatusProps {
    isSyncing: boolean;
    pendingCount: number;
    onSync: () => void;
}

export const SyncStatus: React.FC<SyncStatusProps> = ({ isSyncing, pendingCount, onSync }) => {
    return (
        <button
            onClick={onSync}
            disabled={isSyncing}
            className={`
                flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all
                ${pendingCount > 0
                    ? 'bg-amber-100 text-amber-700 hover:bg-amber-200 border border-amber-200'
                    : 'bg-white text-gray-500 hover:bg-gray-100 border border-transparent'
                }
            `}
            title={pendingCount > 0 ? `${pendingCount} Data belum tersync` : 'Data sudah tersync'}
        >
            <div className={`relative flex items-center justify-center w-5 h-5`}>
                <span className={`text-lg ${isSyncing ? 'animate-spin' : ''}`}>
                    {isSyncing ? '↻' : (pendingCount > 0 ? '☁️' : '✓')}
                </span>
                {pendingCount > 0 && !isSyncing && (
                    <span className="absolute -top-1 -right-1 flex h-3 w-3">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                )}
            </div>

            <span className={`${pendingCount === 0 ? 'hidden md:inline' : 'inline'}`}>
                {isSyncing ? 'Syncing...' : (
                    pendingCount > 0 ? `${pendingCount} Pending` : 'Synced'
                )}
            </span>
        </button>
    );
};
