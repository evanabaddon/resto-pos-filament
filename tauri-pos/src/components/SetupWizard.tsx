import React, { useState } from 'react';
import { api } from '../services/api';
import { syncService } from '../services/sync';

type SetupStep = 'welcome' | 'api-config' | 'syncing' | 'complete';

interface SetupWizardProps {
    onComplete: () => void;
}

export const SetupWizard: React.FC<SetupWizardProps> = ({ onComplete }) => {
    const [step, setStep] = useState<SetupStep>('welcome');
    const [apiUrl, setApiUrl] = useState('');
    const [apiToken, setApiToken] = useState('');
    const [isTestingConnection, setIsTestingConnection] = useState(false);
    const [connectionError, setConnectionError] = useState('');
    const [syncProgress, setSyncProgress] = useState({
        products: false,
        images: false,
        settings: false,
        shift: false
    });

    const handleTestConnection = async () => {
        if (!apiUrl.trim()) {
            setConnectionError('Please enter API URL');
            return;
        }

        setIsTestingConnection(true);
        setConnectionError('');

        try {
            // Set API config
            localStorage.setItem('pos_api_url', apiUrl);
            if (apiToken) localStorage.setItem('pos_api_token', apiToken);

            // Test connection
            await api.testConnection(apiUrl);

            // Connection successful, proceed to sync
            setStep('syncing');
            await performInitialSync();

        } catch (error: any) {
            setConnectionError(error.message || 'Failed to connect to server');
        } finally {
            setIsTestingConnection(false);
        }
    };

    const performInitialSync = async () => {
        try {
            // Sync settings first
            await syncService.syncSettings();
            setSyncProgress(prev => ({ ...prev, settings: true }));

            // Sync products (includes payment methods and categories)
            await syncService.syncProducts();
            setSyncProgress(prev => ({ ...prev, products: true, images: true }));

            // Sync current shift
            await syncService.syncCurrentShift();
            setSyncProgress(prev => ({ ...prev, shift: true }));

            // Mark setup as completed
            localStorage.setItem('setup_completed', 'true');

            // Move to completion screen
            setTimeout(() => {
                setStep('complete');
            }, 500);

        } catch (error) {
            console.error('Initial sync failed:', error);
            setConnectionError('Sync failed. Please check your connection and try again.');
            setStep('api-config');
        }
    };

    const handleComplete = () => {
        onComplete();
    };

    return (
        <div className="fixed inset-0 bg-gradient-to-br from-blue-600 to-purple-700 flex items-center justify-center z-50">
            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 overflow-hidden">
                {/* Welcome Step */}
                {step === 'welcome' && (
                    <div className="p-12 text-center">
                        <div className="text-6xl mb-6">🍽️</div>
                        <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                            Welcome to Resto POS
                        </h1>
                        <p className="text-gray-600 dark:text-gray-300 mb-8 text-lg">
                            Let's get you set up in just a few steps
                        </p>
                        <button
                            onClick={() => setStep('api-config')}
                            className="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold text-lg transition-colors shadow-lg hover:shadow-xl"
                        >
                            Get Started
                        </button>
                    </div>
                )}

                {/* API Configuration Step */}
                {step === 'api-config' && (
                    <div className="p-12">
                        <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            Connect to Server
                        </h2>
                        <p className="text-gray-600 dark:text-gray-300 mb-8">
                            Enter your server details to sync products and settings
                        </p>

                        <div className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    API URL *
                                </label>
                                <input
                                    type="text"
                                    value={apiUrl}
                                    onChange={(e) => setApiUrl(e.target.value)}
                                    placeholder="https://pos.suralaya.id/api"
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    API Token (Optional)
                                </label>
                                <input
                                    type="password"
                                    value={apiToken}
                                    onChange={(e) => setApiToken(e.target.value)}
                                    placeholder="Enter API token if required"
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>

                            {connectionError && (
                                <div className="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                    <p className="text-red-600 dark:text-red-400 text-sm">
                                        ❌ {connectionError}
                                    </p>
                                </div>
                            )}

                            <button
                                onClick={handleTestConnection}
                                disabled={isTestingConnection}
                                className="w-full px-6 py-4 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-xl font-semibold text-lg transition-colors shadow-lg hover:shadow-xl disabled:cursor-not-allowed"
                            >
                                {isTestingConnection ? (
                                    <span className="flex items-center justify-center gap-2">
                                        <svg className="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        Testing Connection...
                                    </span>
                                ) : (
                                    'Connect & Sync'
                                )}
                            </button>
                        </div>
                    </div>
                )}

                {/* Syncing Step */}
                {step === 'syncing' && (
                    <div className="p-12">
                        <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            Setting Up...
                        </h2>
                        <p className="text-gray-600 dark:text-gray-300 mb-8">
                            Please wait while we download your data
                        </p>

                        <div className="space-y-4">
                            <SyncProgressItem
                                label="Settings & Configuration"
                                completed={syncProgress.settings}
                            />
                            <SyncProgressItem
                                label="Products & Categories"
                                completed={syncProgress.products}
                            />
                            <SyncProgressItem
                                label="Product Images"
                                completed={syncProgress.images}
                            />
                            <SyncProgressItem
                                label="Current Shift"
                                completed={syncProgress.shift}
                            />
                        </div>

                        <div className="mt-8 flex items-center justify-center gap-2 text-blue-600 dark:text-blue-400">
                            <svg className="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <span className="font-medium">Syncing...</span>
                        </div>
                    </div>
                )}

                {/* Complete Step */}
                {step === 'complete' && (
                    <div className="p-12 text-center">
                        <div className="text-6xl mb-6">✅</div>
                        <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            All Set!
                        </h2>
                        <p className="text-gray-600 dark:text-gray-300 mb-8 text-lg">
                            Your POS is ready to use. You can now work offline.
                        </p>
                        <button
                            onClick={handleComplete}
                            className="px-8 py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold text-lg transition-colors shadow-lg hover:shadow-xl"
                        >
                            Start Using POS
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
};

const SyncProgressItem: React.FC<{ label: string; completed: boolean }> = ({ label, completed }) => {
    return (
        <div className="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div className={`w-6 h-6 rounded-full flex items-center justify-center ${completed ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'
                }`}>
                {completed && (
                    <svg className="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                    </svg>
                )}
            </div>
            <span className={`font-medium ${completed ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'
                }`}>
                {label}
            </span>
        </div>
    );
};
