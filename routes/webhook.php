<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

Route::prefix('webhook')->group(function () {
    
    // Receive print job dari POS
    Route::post('/print', function (Request $request) {
        try {
            Log::info('🖨️ Print webhook received', $request->all());
            
            // Validasi secret key
            $expectedSecret = config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');
            
            if ($receivedSecret !== $expectedSecret) {
                Log::warning('❌ Unauthorized print webhook attempt', [
                    'expected' => $expectedSecret,
                    'received' => $receivedSecret
                ]);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // Validasi input
            $validated = $request->validate([
                'content' => 'required|string',
                'printer' => 'sometimes|string',
                'division' => 'sometimes|string',
                'sale_id' => 'sometimes|integer',
                'type' => 'sometimes|string|in:receipt,order,test'
            ]);

            // Generate job ID
            $jobId = 'job_' . uniqid() . '_' . time();
            
            // Simpan print job
            $printData = [
                'id' => $jobId,
                'content' => $validated['content'],
                'printer' => $validated['printer'] ?? 'BAR',
                'division' => $validated['division'] ?? 'general',
                'sale_id' => $validated['sale_id'] ?? null,
                'type' => $validated['type'] ?? 'order',
                'created_at' => now()->toISOString(),
                'status' => 'pending',
                'attempts' => 0
            ];
            
            // Simpan ke file JSON
            savePrintJob($printData);
            
            Log::info("✅ Print job queued: {$jobId}");

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Print job queued successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Webhook print error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Get pending print jobs untuk Windows client
    Route::get('/print-jobs', function (Request $request) {
        try {
            // Validasi secret key
            $expectedSecret = config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');
            
            if ($receivedSecret !== $expectedSecret) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $jobs = getPendingPrintJobs();
            
            return response()->json([
                'success' => true,
                'jobs' => $jobs,
                'total' => count($jobs),
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Get print jobs error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Update job status setelah berhasil diprint
    Route::post('/print-job/{jobId}/complete', function ($jobId, Request $request) {
        try {
            // Validasi secret key
            $expectedSecret = config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');
            
            if ($receivedSecret !== $expectedSecret) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $success = markJobCompleted($jobId);
            
            if ($success) {
                Log::info("✅ Print job completed: {$jobId}");
                return response()->json([
                    'success' => true, 
                    'message' => 'Job marked as completed'
                ]);
            } else {
                return response()->json([
                    'success' => false, 
                    'error' => 'Job not found'
                ], 404);
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Complete job error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Health check endpoint
    Route::get('/health', function () {
        try {
            $pendingJobs = getPendingPrintJobs();
            
            return response()->json([
                'status' => 'ok',
                'service' => 'Print Webhook',
                'timestamp' => now()->toISOString(),
                'pending_jobs' => count($pendingJobs),
                'version' => '1.0.0'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Simple test endpoint tanpa auth
    Route::get('/test', function () {
        return response()->json([
            'message' => 'Webhook server is running!',
            'timestamp' => now()->toISOString(),
            'status' => 'active'
        ]);
    });
});

// Helper functions
function savePrintJob(array $jobData): void
{
    try {
        $jobsFile = storage_path('app/print_jobs.json');
        $jobs = [];
        
        if (file_exists($jobsFile)) {
            $content = file_get_contents($jobsFile);
            if (!empty($content)) {
                $jobs = json_decode($content, true) ?? [];
            }
        }
        
        $jobs[] = $jobData;
        file_put_contents($jobsFile, json_encode($jobs, JSON_PRETTY_PRINT));
        
    } catch (\Exception $e) {
        Log::error('Error saving print job: ' . $e->getMessage());
        throw $e;
    }
}

function getPendingPrintJobs(): array
{
    try {
        $jobsFile = storage_path('app/print_jobs.json');
        
        if (!file_exists($jobsFile)) {
            return [];
        }
        
        $content = file_get_contents($jobsFile);
        if (empty($content)) {
            return [];
        }
        
        $allJobs = json_decode($content, true) ?? [];
        
        return array_filter($allJobs, function ($job) {
            return isset($job['status']) && 
                   $job['status'] === 'pending' && 
                   (!isset($job['attempts']) || $job['attempts'] < 3);
        });
        
    } catch (\Exception $e) {
        Log::error('Error getting print jobs: ' . $e->getMessage());
        return [];
    }
}

function markJobCompleted(string $jobId): bool
{
    return updateJobStatus($jobId, 'completed');
}

function markJobFailed(string $jobId, string $error): bool
{
    try {
        $jobsFile = storage_path('app/print_jobs.json');
        
        if (!file_exists($jobsFile)) {
            return false;
        }
        
        $content = file_get_contents($jobsFile);
        if (empty($content)) {
            return false;
        }
        
        $jobs = json_decode($content, true) ?? [];
        $updated = false;
        
        foreach ($jobs as &$job) {
            if (isset($job['id']) && $job['id'] === $jobId) {
                $job['status'] = 'failed';
                $job['error'] = $error;
                $job['completed_at'] = now()->toISOString();
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            file_put_contents($jobsFile, json_encode($jobs, JSON_PRETTY_PRINT));
        }
        
        return $updated;
        
    } catch (\Exception $e) {
        Log::error('Error marking job failed: ' . $e->getMessage());
        return false;
    }
}

function updateJobStatus(string $jobId, string $status): bool
{
    try {
        $jobsFile = storage_path('app/print_jobs.json');
        
        if (!file_exists($jobsFile)) {
            return false;
        }
        
        $content = file_get_contents($jobsFile);
        if (empty($content)) {
            return false;
        }
        
        $jobs = json_decode($content, true) ?? [];
        $updated = false;
        
        foreach ($jobs as &$job) {
            if (isset($job['id']) && $job['id'] === $jobId) {
                $job['status'] = $status;
                $job['completed_at'] = now()->toISOString();
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            file_put_contents($jobsFile, json_encode($jobs, JSON_PRETTY_PRINT));
        }
        
        return $updated;
        
    } catch (\Exception $e) {
        Log::error('Error updating job status: ' . $e->getMessage());
        return false;
    }
}