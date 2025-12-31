import React, { useEffect } from 'react';

interface NotificationProps {
    message: string;
    type: 'success' | 'error' | 'info';
    onClose: () => void;
    duration?: number;
}

const Notification: React.FC<NotificationProps> = ({ message, type, onClose, duration = 3000 }) => {
    useEffect(() => {
        if (duration > 0) {
            const timer = setTimeout(() => {
                onClose();
            }, duration);
            return () => clearTimeout(timer);
        }
    }, [duration, onClose]);

    const bgColors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
    };

    const icons = {
        success: '✅',
        error: '❌',
        info: 'ℹ️',
    };

    return (
        <div className={`fixed top-6 right-6 z-[100] ${bgColors[type]} text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 animate-slide-in min-w-[300px]`}>
            <span className="text-2xl">{icons[type]}</span>
            <div className="flex-1">
                <p className="font-bold text-lg">{type === 'error' ? 'Error' : type === 'success' ? 'Sukses' : 'Info'}</p>
                <p className="text-sm font-medium opacity-90">{message}</p>
            </div>
            <button onClick={onClose} className="text-white/80 hover:text-white p-1">
                ✕
            </button>
        </div>
    );
};

export default Notification;
