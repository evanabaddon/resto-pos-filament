import React, { useState, useEffect } from 'react';
import { dbService } from '../services/db';

interface ShiftModalProps {
    isOpen: boolean;
    mode: 'open' | 'close';
    onClose: () => void;
    onShiftOpened: (shift: any) => void;
    onShiftClosed: () => void;
    onPrintReport?: (shift: any) => void;
    activeShift?: any;
}

const ShiftModal: React.FC<ShiftModalProps> = ({
    isOpen,
    mode,
    onClose,
    onShiftOpened,
    onShiftClosed,
    onPrintReport,
    activeShift
}) => {
    const [amount, setAmount] = useState<string>('');
    const [cashierName, setCashierName] = useState('Admin'); // Default for now
    const [loading, setLoading] = useState(false);

    // For Close Mode
    const [expectedCash, setExpectedCash] = useState(0);
    const [difference, setDifference] = useState(0);
    const [currentSalesTotal, setCurrentSalesTotal] = useState(0);

    useEffect(() => {
        const fetchTotals = async () => {
            if (isOpen && mode === 'close' && activeShift) {
                const salesTotal = await dbService.getShiftSalesTotal(activeShift.id);
                setCurrentSalesTotal(salesTotal);

                // Calculate expected cash
                const initial = activeShift.cash_in_hand || 0;
                // TODO: fetch real expenses/purchases if any
                const expected = initial + salesTotal;
                setExpectedCash(expected);
                setAmount(''); // Reset input
            } else if (isOpen) {
                setAmount('');
            }
        };
        fetchTotals();
    }, [isOpen, mode, activeShift]);

    const handleOpenShift = async () => {
        if (!amount || isNaN(Number(amount))) return alert('Masukkan jumlah modal awal yang valid');

        try {
            setLoading(true);
            const cashInHand = Number(amount);
            const shiftId = await dbService.createShift({
                cashier_name: cashierName,
                cash_in_hand: cashInHand
            });

            const newShift = {
                id: shiftId,
                cashier_name: cashierName,
                cash_in_hand: cashInHand,
                opened_at: new Date().toISOString(),
                status: 'open'
            };

            onShiftOpened(newShift);
            onClose();
        } catch (e) {
            console.error('Failed to open shift:', e);
            alert('Gagal membuka shift');
        } finally {
            setLoading(false);
        }
    };

    const handleCloseShift = async () => {
        if (!amount || isNaN(Number(amount))) return alert('Masukkan jumlah uang di laci');
        if (!activeShift) return;

        try {
            setLoading(true);
            const cashOut = Number(amount);
            const diff = cashOut - expectedCash;

            await dbService.closeShift(activeShift.id, {
                cash_out: cashOut,
                total_cash_sales: currentSalesTotal,
                expected_cash: expectedCash,
                difference: diff
            });

            // Print Report
            if (onPrintReport) {
                onPrintReport({
                    ...activeShift,
                    cash_out: cashOut,
                    total_cash_sales: currentSalesTotal,
                    expected_cash: expectedCash,
                    difference: diff,
                    closed_at: new Date().toISOString()
                });
            }

            alert(`Shift Ditutup.\nSelisih: Rp ${diff.toLocaleString()}`);
            onShiftClosed();
            onClose();
        } catch (e) {
            console.error('Failed to close shift:', e);
            alert('Gagal menutup shift');
        } finally {
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div className="bg-white w-[400px] rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
                <div className="bg-primary-600 px-6 py-4">
                    <h2 className="text-white text-lg font-bold">
                        {mode === 'open' ? '🔓 Buka Shift Kasir' : '🔒 Tutup Shift Kasir'}
                    </h2>
                    <p className="text-primary-100 text-xs mt-0.5">
                        {mode === 'open' ? 'Masukkan modal awal untuk memulai transaksi.' : 'Hitung uang di laci sebelum menutup shift.'}
                    </p>
                </div>

                <div className="p-6 space-y-4">
                    {mode === 'open' && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Nama Kasir</label>
                            <input
                                type="text"
                                value={cashierName}
                                onChange={e => setCashierName(e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                            />
                        </div>
                    )}

                    {mode === 'close' && (
                        <div className="bg-gray-50 p-3 rounded-lg space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-600">Modal Awal</span>
                                <span className="font-mono">{activeShift?.cash_in_hand?.toLocaleString()}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-600">Total Penjualan (Cash)</span>
                                <span className="font-mono">{currentSalesTotal?.toLocaleString()}</span>
                            </div>
                            <div className="flex justify-between border-t border-gray-200 pt-2 font-bold text-gray-800">
                                <span>Total Seharusnya</span>
                                <span>{expectedCash.toLocaleString()}</span>
                            </div>
                        </div>
                    )}

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            {mode === 'open' ? 'Modal Awal (Cash in Hand)' : 'Uang Fisik di Laci (Cash Out)'}
                        </label>
                        <div className="relative">
                            <span className="absolute left-3 top-2.5 text-gray-500 font-bold text-sm">Rp</span>
                            <input
                                type="number"
                                autoFocus
                                value={amount}
                                onChange={e => {
                                    setAmount(e.target.value);
                                    if (mode === 'close') {
                                        setDifference(Number(e.target.value) - expectedCash);
                                    }
                                }}
                                onKeyDown={e => e.key === 'Enter' && (mode === 'open' ? handleOpenShift() : handleCloseShift())}
                                className="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-lg font-bold text-gray-900 focus:ring-2 focus:ring-primary-500 outline-none"
                                placeholder="0"
                            />
                        </div>
                        {mode === 'close' && amount !== '' && (
                            <div className={`text-right text-xs mt-1 font-bold ${difference < 0 ? 'text-red-500' : 'text-green-600'}`}>
                                Selisih: {difference > 0 ? '+' : ''}{difference.toLocaleString()}
                            </div>
                        )}
                    </div>

                    <div className="pt-2 flex gap-3">
                        {/* Close Button available only in Close Mode or if Open Mode (but maybe cancel?) 
                             Actually for Open Shift on startup, we shouldn't allow cancel generally, but for dev we might.
                         */}
                        {mode === 'close' && (
                            <button
                                onClick={onClose}
                                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium"
                            >
                                Batal
                            </button>
                        )}

                        <button
                            onClick={mode === 'open' ? handleOpenShift : handleCloseShift}
                            disabled={loading || !amount}
                            className={`flex-1 px-4 py-2 rounded-lg text-white font-bold shadow-lg transition-all 
                                ${mode === 'open' ? 'bg-primary-600 hover:bg-primary-700' : 'bg-red-600 hover:bg-red-700'}
                                disabled:bg-gray-300 disabled:cursor-not-allowed`}
                        >
                            {loading ? 'Memproses...' : (mode === 'open' ? 'Buka Shift' : 'Tutup Shift')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ShiftModal;
