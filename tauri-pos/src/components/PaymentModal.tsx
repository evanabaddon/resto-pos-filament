import React, { useState, useEffect } from 'react';

interface PaymentModalProps {
    isOpen: boolean;
    total: number;
    paymentMethods: any[];
    onConfirm: (amount: number, methodId: number, methodCode: string) => void;
    onCancel: () => void;
}

const PaymentModal: React.FC<PaymentModalProps> = ({
    isOpen,
    total,
    paymentMethods,
    onConfirm,
    onCancel
}) => {
    const [selectedMethod, setSelectedMethod] = useState<any>(null);
    const [paymentAmount, setPaymentAmount] = useState<string>('');
    const [change, setChange] = useState(0);

    useEffect(() => {
        if (isOpen && paymentMethods.length > 0) {
            // Default to Cash if available
            const cashMethod = paymentMethods.find(m => m.code === 'cash');
            setSelectedMethod(cashMethod || paymentMethods[0]);
            setPaymentAmount('');
            setChange(0);
        }
    }, [isOpen, paymentMethods]);

    useEffect(() => {
        if (selectedMethod?.code === 'cash' && paymentAmount) {
            const amount = Number(paymentAmount);
            setChange(amount - total);
        } else {
            setChange(0);
        }
    }, [paymentAmount, total, selectedMethod]);

    const handleQuickCash = (amount: number) => {
        setPaymentAmount(amount.toString());
    };

    const handleConfirm = () => {
        if (!selectedMethod) return;

        const amount = selectedMethod.code === 'cash' ? Number(paymentAmount) : total;

        if (selectedMethod.code === 'cash' && amount < total) {
            alert('Jumlah uang tidak cukup!');
            return;
        }

        onConfirm(amount, selectedMethod.id, selectedMethod.code);
    };

    const canConfirm = () => {
        if (!selectedMethod) return false;
        if (selectedMethod.code === 'cash') {
            return paymentAmount && Number(paymentAmount) >= total;
        }
        return true; // Non-cash methods don't need amount input
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div className="bg-white w-[500px] rounded-2xl shadow-2xl overflow-hidden border border-gray-100">
                <div className="bg-primary-600 px-6 py-4">
                    <h2 className="text-white text-xl font-bold">💳 Pembayaran</h2>
                    <p className="text-primary-100 text-sm mt-0.5">Pilih metode pembayaran dan konfirmasi</p>
                </div>

                <div className="p-6 space-y-6">
                    {/* Total Bill */}
                    <div className="bg-gradient-to-r from-primary-50 to-primary-100 p-4 rounded-xl">
                        <div className="text-sm text-primary-600 font-medium">Total Tagihan</div>
                        <div className="text-3xl font-bold text-primary-900 mt-1">
                            Rp {total.toLocaleString('id-ID')}
                        </div>
                    </div>

                    {/* Payment Method Selection */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran</label>
                        <div className="grid grid-cols-3 gap-3">
                            {paymentMethods.map(method => (
                                <button
                                    key={method.id}
                                    onClick={() => setSelectedMethod(method)}
                                    className={`p-4 rounded-lg border-2 transition-all ${selectedMethod?.id === method.id
                                            ? 'border-primary-600 bg-primary-50 text-primary-900'
                                            : 'border-gray-200 hover:border-primary-300 text-gray-700'
                                        }`}
                                >
                                    <div className="text-2xl mb-1">
                                        {method.code === 'cash' ? '💵' : method.code === 'qris' ? '📱' : '💳'}
                                    </div>
                                    <div className="text-sm font-medium">{method.name}</div>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Cash Input (only for cash method) */}
                    {selectedMethod?.code === 'cash' && (
                        <div className="space-y-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Uang Diterima</label>
                                <div className="relative">
                                    <span className="absolute left-4 top-3.5 text-gray-500 font-bold">Rp</span>
                                    <input
                                        type="number"
                                        autoFocus
                                        value={paymentAmount}
                                        onChange={e => setPaymentAmount(e.target.value)}
                                        onKeyDown={e => e.key === 'Enter' && canConfirm() && handleConfirm()}
                                        className="w-full border-2 border-gray-300 rounded-lg pl-12 pr-4 py-3 text-xl font-bold text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                                        placeholder="0"
                                    />
                                </div>
                            </div>

                            {/* Quick Cash Buttons */}
                            <div className="grid grid-cols-4 gap-2">
                                <button
                                    onClick={() => handleQuickCash(total)}
                                    className="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700"
                                >
                                    Pas
                                </button>
                                <button
                                    onClick={() => handleQuickCash(50000)}
                                    className="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700"
                                >
                                    50k
                                </button>
                                <button
                                    onClick={() => handleQuickCash(100000)}
                                    className="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700"
                                >
                                    100k
                                </button>
                                <button
                                    onClick={() => handleQuickCash(200000)}
                                    className="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700"
                                >
                                    200k
                                </button>
                            </div>

                            {/* Change Display */}
                            {paymentAmount && Number(paymentAmount) >= total && (
                                <div className="bg-green-50 p-4 rounded-lg border border-green-200">
                                    <div className="text-sm text-green-600 font-medium">Kembalian</div>
                                    <div className="text-2xl font-bold text-green-900 mt-1">
                                        Rp {change.toLocaleString('id-ID')}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Non-Cash Info */}
                    {selectedMethod?.code !== 'cash' && (
                        <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <div className="text-sm text-blue-600">
                                ℹ️ Pastikan pembayaran {selectedMethod?.name} telah diterima sebelum melanjutkan.
                            </div>
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div className="flex gap-3 pt-2">
                        <button
                            onClick={onCancel}
                            className="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-all"
                        >
                            Batal
                        </button>
                        <button
                            onClick={handleConfirm}
                            disabled={!canConfirm()}
                            className={`flex-1 px-4 py-3 rounded-lg text-white font-bold shadow-lg transition-all ${canConfirm()
                                    ? 'bg-primary-600 hover:bg-primary-700'
                                    : 'bg-gray-300 cursor-not-allowed'
                                }`}
                        >
                            Bayar & Cetak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default PaymentModal;
