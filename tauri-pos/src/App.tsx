import { useState, useEffect, useRef, useMemo, useCallback } from 'react';
import { ThemeProvider } from './context/ThemeContext';
import { dbService } from './services/db';
import { syncService } from './services/sync';
import { printerService, type PrinterSettings } from './services/printer';
// Components
import { TopBar } from './components/TopBar';
import { ProductGrid } from './components/ProductGrid';
import { CartSidebar } from './components/CartSidebar';
import ShiftModal from './components/ShiftModal';
import PaymentModal from './components/PaymentModal';
import Notification from './components/Notification';
import ConfirmModal from './components/ConfirmModal';
import SplitBillModal from './components/SplitBillModal';
import JoinBillModal from './components/JoinBillModal';
import DraftsModal from './components/DraftsModal';
import { SyncIssuesModal } from './components/SyncIssuesModal';
import SettingsModal from './components/SettingsModal';
import { useCart } from './hooks/useCart';
import { useDrafts } from './hooks/useDrafts';
import { useTransaction } from './hooks/useTransaction';
import type { Product, Category, CartItem } from './types';

function PosApp() {
    const [products, setProducts] = useState<Product[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [selectedCategory, setSelectedCategory] = useState<number | 'SEMUA'>('SEMUA');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [orderNumber] = useState(() => Date.now().toString().slice(-6));
    const [customerName, setCustomerName] = useState('');
    const [orderType, setOrderType] = useState<'Dine In' | 'Take Away'>('Dine In');
    const [tableNumber, setTableNumber] = useState('');
    const [isSyncing, setIsSyncing] = useState(false);
    const [pendingCount, setPendingCount] = useState(0);

    // Notification State
    const [notification, setNotification] = useState<{ message: string, type: 'success' | 'error' | 'info' } | null>(null);

    const showNotification = useCallback((message: string, type: 'success' | 'error' | 'info') => {
        setNotification({ message, type });
    }, []);

    // Business Settings (Moved up for useCart)
    const [settings, setSettings] = useState<any>(() => {
        const stored = localStorage.getItem('pos_settings');
        return stored ? JSON.parse(stored) : { tax_rate: 0 };
    });

    // Printer Settings stored in App (global)
    const [printerSettings, setPrinterSettings] = useState<PrinterSettings>(() => {
        const stored = localStorage.getItem('pos_printer_settings');
        if (stored) {
            const parsed = JSON.parse(stored);
            return {
                cashierPrinter: parsed.cashierPrinter || '',
                cashierPaperWidth: parsed.cashierPaperWidth || '58mm',
                autoPrint: parsed.autoPrint || false,
                categoryMappings: parsed.categoryMappings || []
            };
        }
        return {
            cashierPrinter: '',
            cashierPaperWidth: '58mm',
            autoPrint: false,
            categoryMappings: []
        };
    });

    // Cart Hook
    const {
        cart,
        setCart,
        addToCart,
        updateQuantity,
        updateItemNotes,
        calculateTotal,
        clearCart,
        discount,
        setDiscount
    } = useCart(settings, showNotification);

    // Drafts Hook
    const [showDrafts, setShowDrafts] = useState(false);
    const {
        drafts,
        isLoadingDrafts,
        loadDrafts,
        activeDraft,
        setActiveDraft,
        saveDraft,
        deleteDraft,
        handleMergeDrafts,
        transactionTab,
        setTransactionTab,
        isJoinModalOpen,
        setIsJoinModalOpen
    } = useDrafts(settings, showNotification);

    // Confirm Modal State
    const [confirmModal, setConfirmModal] = useState<{
        isOpen: boolean;
        title: string;
        message: string;
        onConfirm: () => void;
        isDestructive?: boolean;
    }>({
        isOpen: false,
        title: '',
        message: '',
        onConfirm: () => { },
        isDestructive: false
    });

    const closeConfirmModal = () => {
        setConfirmModal(prev => ({ ...prev, isOpen: false }));
    };



    // Shift Management
    const [activeShift, setActiveShift] = useState<any>(null); // { id, cashier_name, cash_in_hand, etc }
    const [isShiftModalOpen, setIsShiftModalOpen] = useState(false);
    const [shiftModalMode, setShiftModalMode] = useState<'open' | 'close'>('open');

    // Payment Modal
    const [paymentMethods, setPaymentMethods] = useState<any[]>([]);





    const handleShiftOpened = (shift: any) => {
        setActiveShift(shift);
        setIsShiftModalOpen(false);
        handleSync(); // Trigger sync immediately
    };

    const handleShiftClosed = () => {
        setActiveShift(null);
        setIsShiftModalOpen(false);
        handleSync(); // Trigger sync immediately
    };

    const handlePrintShiftReport = async (shiftData: any) => {
        if (!printerSettings.cashierPrinter) return;
        try {
            const reportText = printerService.generateShiftReportText(
                shiftData,
                settings,
                printerSettings.cashierPaperWidth || '58mm'
            );
            await printerService.printJob(printerSettings.cashierPrinter, reportText);
        } catch (e) {
            console.error('Failed to print shift report:', e);
            showNotification('Gagal mencetak laporan shift', 'error');
        }
    };



    const [showSettings, setShowSettings] = useState(false);
    const [showSyncIssues, setShowSyncIssues] = useState(false);
    const [syncErrorCount, setSyncErrorCount] = useState(0);
    // Removed local states that were moved to SettingsModal (settingsTab, settingsApiUrl, availablePrinters, newMapping)

    // Printer Settings moved to top

    const handleSaveSettings = useCallback((newApiUrl: string) => {
        // 1. Update Settings with new API URL
        const updatedSettings = { ...settings, apiUrl: newApiUrl };
        setSettings(updatedSettings);
        localStorage.setItem('pos_settings', JSON.stringify(updatedSettings));

        // 2. Persist Printer Settings (already updated in state via setPrinterSettings)
        localStorage.setItem('pos_printer_settings', JSON.stringify(printerSettings));

        setShowSettings(false);
        showNotification('✅ Pengaturan disimpan!', 'success');

        if (newApiUrl !== settings.apiUrl) {
            window.location.reload();
        }
    }, [settings, printerSettings, showNotification]);

    // Product Search State
    const [searchQuery, setSearchQuery] = useState('');
    const searchInputRef = useRef<HTMLInputElement>(null);


    // INITIALIZATION & SYNC
    useEffect(() => {
        const initApp = async () => {
            try {
                setLoading(true);
                // 1. Init DB
                await dbService.init();

                // 2. Load Local Data First (Instant Load)
                await loadLocalData();

                // 3. Check for Open Shift
                const openShift = await dbService.getOpenShift();
                if (openShift) {
                    console.log('✅ Found active shift:', openShift);
                    setActiveShift(openShift);
                } else {
                    console.log('ℹ️ No active shift found.');
                    // Force Open Shift Modal
                    setShiftModalMode('open');
                    setIsShiftModalOpen(true);
                }

                // 4. Trigger Background Sync
                handleSync();

            } catch (err: any) {
                console.error('App init failed:', err);
                setError(`Gagal memuat database lokal: ${err.message || JSON.stringify(err)}`);
            } finally {
                setLoading(false);
            }
        };

        initApp();
    }, []);

    const loadLocalData = async () => {
        const localProducts = await dbService.getProducts();
        const localCategories = await dbService.getCategories();
        const localPaymentMethods = await dbService.getPaymentMethods();
        setProducts(localProducts);
        setCategories(localCategories);
        setPaymentMethods(localPaymentMethods);

        // Load Pending Sales Count
        try {
            const pendingSales = await dbService.getPendingSales();
            const pendingUploads = pendingSales.filter(s => s.status === 'pending');
            setPendingCount(pendingUploads.length);

            const issues = await dbService.getSyncIssues();
            setSyncErrorCount(issues.length);
        } catch (e) {
            console.error('Failed to load pending count:', e);
        }

        // Load Settings
        const storedSettings = localStorage.getItem('pos_settings');
        if (storedSettings) {
            setSettings(JSON.parse(storedSettings));
        }
    };

    // Transaction Hook
    const {
        isPaymentModalOpen, setIsPaymentModalOpen,
        isSplitModalOpen, setIsSplitModalOpen,
        splitCart, setSplitCart,
        handleCheckout,
        handleSplitRequest,
        handlePaymentConfirm,
        printOrder
    } = useTransaction({
        cart,
        customerName,
        tableNumber,
        orderType,
        discount,
        settings,
        printerSettings,
        activeShift,
        showNotification,
        clearCart,
        setCart,
        activeDraft,
        setActiveDraft,
        loadLocalData,
        setIsShiftModalOpen,
        setShiftModalMode
    });

    const handleSync = async () => {
        setIsSyncing(true);
        try {
            await syncService.syncAll(activeShift?.id);
        } catch (error) {
            console.error('Manual sync failed:', error);
        } finally {
            // Refresh local view regardless of sync success/failure
            await loadLocalData();
            setIsSyncing(false);
        }
    };


    const handleSaveDraft = useCallback(async () => {
        const success = await saveDraft(cart, customerName, tableNumber, orderType, discount, calculateTotal);
        if (success) {
            clearCart();
            setCustomerName('');
            setTableNumber('');
            loadLocalData(); // Refresh stock in grid
        }
    }, [saveDraft, cart, customerName, tableNumber, orderType, discount, calculateTotal, clearCart, loadLocalData]);

    const handleClearCart = useCallback(() => {
        clearCart();
        setCustomerName('');
        setTableNumber('');
        setActiveDraft(null);
    }, [clearCart, setActiveDraft]);

    const processResumeDraft = useCallback(async (draft: any) => {
        closeConfirmModal();

        try {
            // Restore Cart
            const restoredCart: CartItem[] = draft.data.items.map((item: any) => ({
                product: {
                    id: item.product_id,
                    name: item.product_name,
                    price: Number(item.price),
                    // Mock other fields if needed or load from products array
                    // Ideally we should lookup from 'products' state to get latest stock/active status
                    // But for now purely for cart display:
                    category_id: item.category_id
                } as Product,
                quantity: Number(item.quantity),
                subtotal: Number(item.subtotal),
                notes: item.notes
            }));

            // Attempt to link to real products for stock checks
            const linkedCart = restoredCart.map(item => {
                const realProduct = products.find(p => p.id === item.product.id);
                if (realProduct) {
                    return { ...item, product: { ...realProduct, ...item.product } };
                }
                return item;
            });

            setCart(linkedCart);
            setCustomerName(draft.data.customer_name || '');
            setOrderType(draft.data.order_type || 'Dine In');
            setTableNumber(draft.data.table_number || '');
            setDiscount(Number(draft.data.discount || 0));

            // Track this draft so we can delete it only after Checkout or Update
            setActiveDraft({ id: draft.id, source: draft.source });

            setShowDrafts(false);

            showNotification('✅ Draft Berhasil Diresume!', 'info');

        } catch (err) {
            console.error('Failed to resume draft:', err);
            showNotification('❌ Gagal resume draft', 'error');
        }
    }, [products, setCart, setActiveDraft, setShowDrafts, showNotification]);

    const handleResumeDraft = useCallback(async (draft: any) => {
        if (cart.length > 0) {
            setConfirmModal({
                isOpen: true,
                title: 'Timpa Keranjang?',
                message: 'Keranjang saat ini tidak kosong. Apakah Anda yakin ingin menggantinya dengan draft ini?',
                isDestructive: true,
                onConfirm: () => {
                    closeConfirmModal();
                    processResumeDraft(draft);
                }
            });
            return;
        }
        processResumeDraft(draft);
    }, [cart.length, processResumeDraft, setConfirmModal]);

    const handleDeleteDraftWrapper = useCallback((draft: any, e: React.MouseEvent) => {
        e.stopPropagation();

        setConfirmModal({
            isOpen: true,
            title: 'Hapus Draft?',
            message: 'Draft ini akan dihapus secara permanen dan tidak dapat dikembalikan.',
            isDestructive: true,
            onConfirm: async () => {
                closeConfirmModal();
                await deleteDraft(draft);
                loadDrafts(transactionTab);
            }
        });
    }, [deleteDraft, loadDrafts, transactionTab, setConfirmModal]);

    const handleReprint = useCallback(async (draft: any, e: React.MouseEvent) => {
        e.stopPropagation();
        if (!printerSettings.cashierPrinter) {
            showNotification('⚠️ Printer utama belum diatur!', 'error');
            return;
        }

        try {
            const saleData = draft.data;
            const invoiceNum = saleData.invoice_number || (draft.source === 'local' ? `OFFLINE-${draft.id}` : `SERVER-${draft.id}`);

            showNotification('🖨️ Mencetak ulang...', 'info');

            // 1. Print to Cashier
            const receiptText = printerService.generateReceiptText(
                { ...saleData, invoice_number: invoiceNum },
                settings,
                printerSettings.cashierPaperWidth || '58mm'
            );
            await printerService.printJob(printerSettings.cashierPrinter, receiptText);

            // 2. Process Mappings (Kitchen/Bar/etc)
            const printerGroups: Record<string, { items: any[], paperWidth: '58mm' | '80mm' }> = {};

            if (saleData.items && Array.isArray(saleData.items)) {
                saleData.items.forEach((item: any) => {
                    let catId = item.category_id;

                    // Fallback lookup if catId missing (for backward compatibility)
                    if (!catId) {
                        const localProduct = products.find(p => p.id === item.product_id);
                        if (localProduct) catId = localProduct.category_id;
                    }

                    const mapping = printerSettings.categoryMappings && printerSettings.categoryMappings.find(m => m.categoryId === catId);

                    if (mapping && mapping.printerName) {
                        if (!printerGroups[mapping.printerName]) {
                            printerGroups[mapping.printerName] = {
                                items: [],
                                paperWidth: mapping.paperWidth || '58mm'
                            };
                        }
                        printerGroups[mapping.printerName].items.push(item);
                    }
                });
            }

            // Print each group
            for (const [targetPrinter, group] of Object.entries(printerGroups)) {
                const ticketOrder = {
                    ...saleData,
                    invoice_number: invoiceNum,
                    items: group.items,
                    subtotal: 0, tax: 0, discount: 0, total: 0
                };
                const ticketText = printerService.generateReceiptText(ticketOrder, settings, group.paperWidth);
                await printerService.printJob(targetPrinter, ticketText);
            }

            showNotification('✅ Cetak ulang berhasil!', 'success');
        } catch (error: any) {
            console.error('Reprint failed:', error);
            showNotification('❌ Gagal mencetak ulang: ' + error.message, 'error');
        }
    }, [printerSettings, settings, products, showNotification]);

    const filteredProducts = useMemo(() => {
        return products.filter(p => {
            const matchesCategory = selectedCategory === 'SEMUA' || p.category_id === selectedCategory;
            const matchesSearch = p.name.toLowerCase().includes(searchQuery.toLowerCase());
            return matchesCategory && matchesSearch;
        });
    }, [products, selectedCategory, searchQuery]);
    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 flex-col transition-colors duration-200">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mb-4"></div>
                <p className="text-gray-600 dark:text-gray-300">Memuat Sistem POS...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="flex items-center justify-center min-h-screen bg-red-50 dark:bg-gray-900 p-8 transition-colors duration-200">
                <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-md w-full text-center border border-red-100 dark:border-red-900/30">
                    <h2 className="text-xl font-bold text-red-600 dark:text-red-400 mb-2">System Error</h2>
                    <p className="text-gray-600 dark:text-gray-300 mb-4">{error}</p>
                    <button onClick={() => window.location.reload()} className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                        Reload App
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex h-screen bg-gray-100 dark:bg-gray-900 overflow-hidden font-sans text-gray-900 dark:text-gray-100">
            {/* LEFT: Products Section */}
            <div className="flex-1 flex flex-col min-w-0">
                <TopBar
                    isSyncing={isSyncing}
                    pendingCount={pendingCount}
                    errorCount={syncErrorCount}
                    searchQuery={searchQuery}
                    setSearchQuery={setSearchQuery}
                    onSearchInputRef={searchInputRef}
                    onOpenDrafts={() => { loadDrafts(); setShowDrafts(true); }}
                    activeShift={activeShift}
                    onToggleShift={() => {
                        if (activeShift) {
                            setShiftModalMode('close');
                            setIsShiftModalOpen(true);
                        } else {
                            setShiftModalMode('open');
                            setIsShiftModalOpen(true);
                        }
                    }}
                    onManualSync={handleSync}
                    onOpenSettings={() => setShowSettings(true)}
                    onOpenSyncIssues={() => setShowSyncIssues(true)}
                />

                {/* SETTINGS MODAL */}
                <SettingsModal
                    isOpen={showSettings}
                    onClose={() => setShowSettings(false)}
                    printerSettings={printerSettings}
                    onUpdatePrinterSettings={setPrinterSettings}
                    categories={categories}
                    currentApiUrl={localStorage.getItem('pos_api_url') || 'http://localhost:8000/api'}
                    onSave={handleSaveSettings}
                />

                <SyncIssuesModal
                    isOpen={showSyncIssues}
                    onClose={() => setShowSyncIssues(false)}
                    onIssuesResolved={loadLocalData}
                />

                {/* DRAFTS MODAL */}
                <DraftsModal
                    isOpen={showDrafts}
                    onClose={() => setShowDrafts(false)}
                    activeTab={transactionTab}
                    onTabChange={(tab) => {
                        setTransactionTab(tab);
                        loadDrafts(tab);
                    }}
                    drafts={drafts}
                    isLoading={isLoadingDrafts}
                    onDelete={handleDeleteDraftWrapper}
                    onResume={handleResumeDraft}
                    onReprint={handleReprint}
                    onOpenJoin={() => setIsJoinModalOpen(true)}
                />

                <ProductGrid
                    categories={categories}
                    selectedCategory={selectedCategory}
                    setSelectedCategory={setSelectedCategory}
                    filteredProducts={filteredProducts}
                    addToCart={addToCart}
                />
            </div>

            <CartSidebar
                cart={cart}
                orderNumber={orderNumber}
                customerName={customerName}
                setCustomerName={setCustomerName}
                orderType={orderType}
                setOrderType={setOrderType}
                tableNumber={tableNumber}
                setTableNumber={setTableNumber}
                updateQuantity={updateQuantity}
                updateItemNotes={updateItemNotes}
                discount={discount}
                setDiscount={setDiscount}
                subtotal={cart.reduce((sum, item) => sum + Number(item.subtotal), 0)}
                tax={(cart.reduce((sum, item) => sum + Number(item.subtotal), 0) * (settings?.tax_rate || 0)) / 100}
                total={calculateTotal()}
                taxRate={settings?.tax_rate || 0}
                onCheckout={handleCheckout}
                onSaveDraft={handleSaveDraft}
                onClearCart={handleClearCart}
                printOrder={printOrder}
                printerSettings={printerSettings}
                settings={settings}
                onSplitBill={() => setIsSplitModalOpen(true)}
            />

            <SplitBillModal
                isOpen={isSplitModalOpen}
                cart={cart}
                onClose={() => setIsSplitModalOpen(false)}
                onSplit={handleSplitRequest}
            />

            <JoinBillModal
                isOpen={isJoinModalOpen}
                drafts={drafts}
                onClose={() => setIsJoinModalOpen(false)}
                onMerge={async (ids) => {
                    const success = await handleMergeDrafts(ids);
                    if (success) {
                        setIsJoinModalOpen(false);
                        setShowDrafts(false); // Return to main page
                        loadLocalData();      // Refresh stock
                    }
                }}
            />

            <ShiftModal
                isOpen={isShiftModalOpen}
                mode={shiftModalMode}
                onClose={() => {
                    if (activeShift) setIsShiftModalOpen(false);
                }}
                onShiftOpened={handleShiftOpened}
                onShiftClosed={handleShiftClosed}
                onPrintReport={handlePrintShiftReport}
                activeShift={activeShift}
            />

            <PaymentModal
                isOpen={isPaymentModalOpen}
                total={splitCart ? calculateTotal(splitCart) : calculateTotal()}
                paymentMethods={paymentMethods}
                onConfirm={handlePaymentConfirm}
                onCancel={() => {
                    setIsPaymentModalOpen(false);
                    if (splitCart) setSplitCart(null); // Cancel split if modal closed
                }}
            />

            {notification && (
                <Notification
                    message={notification.message}
                    type={notification.type}
                    onClose={() => setNotification(null)}
                />
            )}

            <ConfirmModal
                isOpen={confirmModal.isOpen}
                title={confirmModal.title}
                message={confirmModal.message}
                onConfirm={confirmModal.onConfirm}
                onCancel={closeConfirmModal}
                isDestructive={confirmModal.isDestructive}
            />
        </div>
    );

}

function App() {
    return (
        <ThemeProvider>
            <PosApp />
        </ThemeProvider>
    );
}

export default App;
