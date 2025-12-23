<?php

namespace App\Services;

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    protected string $apiBaseUrl = 'https://licenci.tert.me/api/license/validate';

    /**
     * Validate a license for a specific module.
     */
    public function validateAndCache(string $module, ?string $licenseKey): array
    {
        if (empty($licenseKey)) {
            $this->clearLicenseCache($module);
            return ['valid' => false, 'error' => 'License key is empty'];
        }

        try {
            $domain = request()->getHost();

            // For local development, allow localhost or actual domain
            if ($domain === '127.0.0.1' || $domain === 'localhost') {
                // In production, the user would provide the real domain.
                // For now, we use the host as requested.
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->post($this->apiBaseUrl, [
                    'license_key' => $licenseKey,
                    'domain' => $domain,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['valid']) && $data['valid']) {
                    $this->storeSignedLicense($module, $data);
                    return $data;
                } else {
                    $this->clearLicenseCache($module);
                    return [
                        'valid' => false,
                        'error' => $data['error'] ?? 'License tidak valid'
                    ];
                }
            }

            return ['valid' => false, 'error' => 'Gagal menghubungi server lisensi.'];
        } catch (\Exception $e) {
            Log::error("License validation error for module {$module}: " . $e->getMessage());
            return ['valid' => false, 'error' => 'Sistem lisensi error.'];
        }
    }

    /**
     * Check if a module is valid by verifying the signed cache.
     */
    public function isValid(string $module): bool
    {
        $settings = app(GeneralSettings::class);

        // Basic check: is it even enabled?
        $isEnabled = match ($module) {
            'hrm' => $settings->enable_hrm,
            'kds' => $settings->enable_kds,
            'crm' => $settings->enable_crm,
            'wa_center' => $settings->enable_wa_center,
            'ai_forecasting' => $settings->enable_ai_forecasting,
            'menu_engineering' => $settings->enable_menu_engineering,
            'self_order' => $settings->enable_self_order,
            'fiscal' => $settings->enable_fiscal_planning,
            default => false,
        };

        if (!$isEnabled)
            return false;

        // Verify signed cache
        $cacheData = Cache::get($this->getCacheKey($module));

        if (!$cacheData || !is_array($cacheData)) {
            // No cache? Re-validate if key exists
            $key = $this->getLicenseKey($module, $settings);
            if ($key) {
                $result = $this->validateAndCache($module, $key);
                return $result['valid'] ?? false;
            }
            return false;
        }

        // Integrity check: Verify signature to prevent manual cache editing
        $data = $cacheData['data'] ?? null;
        $signature = $cacheData['signature'] ?? null;

        if (!$data || !$signature)
            return false;

        $expectedSignature = hash_hmac('sha256', json_encode($data), config('app.key'));

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning("Potential license crack attempt detected for module: {$module}");
            $this->clearLicenseCache($module);
            return false;
        }

        // Domain check
        if (($data['domain'] ?? '') !== request()->getHost()) {
            return false;
        }

        // Expiry check
        $expiresAt = isset($data['expires_at']) ? strtotime($data['expires_at']) : 0;
        if ($expiresAt < time()) {
            // Expired, try to re-validate once to be sure
            $key = $this->getLicenseKey($module, $settings);
            $result = $this->validateAndCache($module, $key);
            return $result['valid'] ?? false;
        }

        return true;
    }

    /**
     * Get license information from cache.
     */
    public function getLicenseInfo(string $module): ?array
    {
        $cacheData = Cache::get($this->getCacheKey($module));
        return $cacheData['data'] ?? null;
    }

    protected function storeSignedLicense(string $module, array $data): void
    {
        $data['domain'] = request()->getHost(); // Store domain for later verification

        $signedData = [
            'data' => $data,
            'signature' => hash_hmac('sha256', json_encode($data), config('app.key')),
        ];

        // Cache for 7 days (or until actual expiry if sooner)
        $expiresAt = isset($data['expires_at']) ? strtotime($data['expires_at']) : 0;
        $expiryInSeconds = min(3600 * 24 * 7, max(0, $expiresAt - time()));

        Cache::put($this->getCacheKey($module), $signedData, $expiryInSeconds);
    }

    protected function clearLicenseCache(string $module): void
    {
        Cache::forget($this->getCacheKey($module));
    }

    protected function getCacheKey(string $module): string
    {
        return "license_{$module}_secured";
    }

    protected function getLicenseKey(string $module, GeneralSettings $settings): ?string
    {
        return match ($module) {
            'hrm' => $settings->hrm_license_key,
            'kds' => $settings->kds_license_key,
            'crm' => $settings->crm_license_key,
            'wa_center' => $settings->wa_license_key,
            'ai_forecasting' => $settings->ai_forecasting_license_key,
            'menu_engineering' => $settings->menu_engineering_license_key,
            'self_order' => $settings->self_order_license_key,
            'fiscal' => $settings->fiscal_license_key,
            default => null,
        };
    }
}
