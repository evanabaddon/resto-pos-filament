import React from 'react';
import { SyncStatus } from './SyncStatus';

interface TopBarProps {
    isSyncing: boolean;
    pendingCount: number;
    searchQuery: string;
    setSearchQuery: (query: string) => void;
    onSearchInputRef?: React.RefObject<HTMLInputElement | null>;
    onOpenDrafts: () => void;
    activeShift: any;
    onToggleShift: () => void;
    onManualSync: () => void;
    onOpenSettings: () => void;
    errorCount?: number;
    onOpenSyncIssues?: () => void;
    isOnline?: boolean;
}

export const TopBar: React.FC<TopBarProps> = ({
    isSyncing,
    pendingCount,
    errorCount,
    searchQuery,
    setSearchQuery,
    onSearchInputRef,
    onOpenDrafts,
    activeShift,
    onToggleShift,
    onManualSync,
    onOpenSettings,
    onOpenSyncIssues,
    isOnline = true
}) => {
    return (
        <header className="bg-white dark:bg-gray-800 dark:border-b dark:border-gray-700 shadow-sm px-6 py-4 flex justify-between items-center z-10 transition-all duration-200">
            <div className="flex items-center gap-4">
                <h1 className="text-2xl font-bold text-primary-600 dark:text-primary-400 tracking-tight" style={{ fontFamily: "'Outfit', sans-serif" }}>🍽️ Resto POS</h1>

                {/* Connection Status Badge */}
                <div className={`px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1.5 transition-all ${isOnline
                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                    }`}>
                    <span className={`w-2 h-2 rounded-full ${isOnline ? 'bg-green-500 animate-pulse' : 'bg-red-500'
                        }`} />
                    {isOnline ? 'Online' : 'Offline'}
                </div>

                {/* Realtime Clock */}
                <ClockWidget />
            </div>

            <div className="flex items-center gap-3">
                <div className="relative group">
                    <input
                        ref={onSearchInputRef}
                        type="text"
                        placeholder="Cari menu..."
                        className="pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 dark:text-white focus:bg-white dark:focus:bg-gray-800 focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 w-64 transition-all focus:w-80 outline-none text-sm group-hover:bg-white dark:group-hover:bg-gray-600 group-hover:border-gray-300 dark:group-hover:border-gray-500"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                    />
                    <span className="absolute left-3 top-2.5 text-gray-400 group-focus-within:text-primary-500 transition-colors">🔍</span>
                </div>

                <button
                    onClick={onOpenDrafts}
                    className="p-2.5 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-primary-600 dark:hover:text-primary-400 rounded-xl transition-all active:scale-95 flex items-center gap-1 border border-transparent hover:border-gray-200 dark:hover:border-gray-600"
                    title="Buka Draft"
                >
                    <span className="text-xl">📁</span>
                    <span className="hidden lg:inline text-sm font-semibold">Transaksi</span>
                </button>

                {/* Shift Button */}
                <button
                    onClick={onToggleShift}
                    className={`px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shadow-sm active:scale-95
                        ${activeShift
                            ? 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 hover:shadow-md dark:bg-green-900/20 dark:text-green-400 dark:border-green-800 dark:hover:bg-green-900/30'
                            : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 animate-pulse hover:shadow-md dark:bg-red-900/20 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/30'}
                    `}
                    title={activeShift ? 'Tutup Shift' : 'Buka Shift'}
                >
                    <span>{activeShift ? '🔓' : '🔒'}</span>
                    <span className="hidden sm:inline">{activeShift ? 'Shift Open' : 'Shift Closed'}</span>
                </button>

                <SyncStatus
                    isSyncing={isSyncing}
                    pendingCount={pendingCount}
                    errorCount={errorCount}
                    onSync={onManualSync}
                    onOpenIssues={onOpenSyncIssues}
                />

                <button
                    onClick={onOpenSettings}
                    className="p-2.5 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-200 rounded-xl transition-all active:scale-95 border border-transparent hover:border-gray-200 dark:hover:border-gray-600"
                    title="Pengaturan"
                >
                    <span className="text-xl">⚙️</span>
                </button>
            </div>
        </header>
    );
};

const ClockWidget: React.FC = () => {
    const [time, setTime] = React.useState(new Date());

    React.useEffect(() => {
        const timer = setInterval(() => setTime(new Date()), 1000);
        return () => clearInterval(timer);
    }, []);

    return (
        <div className="hidden md:flex flex-col items-start justify-center ml-2 px-2 border-l border-gray-200 dark:border-gray-700">
            <div className="text-sm font-bold text-gray-700 dark:text-gray-200 leading-none">
                {time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false })}
            </div>
            <div className="text-[10px] font-medium text-gray-500 dark:text-gray-400 leading-none mt-1">
                {time.toLocaleDateString([], { weekday: 'short', day: 'numeric', month: 'short' })}
            </div>
        </div>
    );
};
