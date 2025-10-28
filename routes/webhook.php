<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::prefix('webhook')->group(function () {
    
    // Receive print job dari POS
    Route::post('/print', function (Request $request) {
        Log::info('🖨️ Print webhook received', $request->all());
        
        // Validasi secret key
        $expectedSecret = config('app.print_secret', 'default-print-secret');
        if ($request->header('X-Print-Secret') !== $expectedSecret) {
            Log::warning('❌ Unauthorized print webhook attempt');
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
        $jobId = uniqid('job_');
        
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
        
        // Simpan ke file JSON (bisa diganti dengan database)
        $this->savePrintJob($printData);
        
        Log::info("✅ Print job queued: {$jobId}");
        
        return response()->json([
            'success' => true,
            'job_id' => $jobId,
            'message' => 'Print job queued successfully'
        ]);
    });
    
    // Get pending print jobs untuk Windows client
    Route::get('/print-jobs', function (Request $request) {
        // Validasi secret key
        $expectedSecret = config('app.print_secret', 'default-print-secret');
        if ($request->header('X-Print-Secret') !== $expectedSecret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $jobs = $this->getPendingPrintJobs();
        
        return response()->json([
            'success' => true,
            'jobs' => $jobs,
            'total' => count($jobs)
        ]);
    });
    
    // Update job status setelah berhasil diprint
    Route::post('/print-job/{jobId}/complete', function ($jobId, Request $request) {
        // Validasi secret key
        $expectedSecret = config('app.print_secret', 'default-print-secret');
        if ($request->header('X-Print-Secret') !== $expectedSecret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $success = $this->markJobCompleted($jobId);
        
        if ($success) {
            Log::info("✅ Print job completed: {$jobId}");
            return response()->json(['success' => true, 'message' => 'Job marked as completed']);
        } else {
            return response()->json(['success' => false, 'error' => 'Job not found'], 404);
        }
    });
    
    // Update job status jika gagal
    Route::post('/print-job/{jobId}/failed', function ($jobId, Request $request) {
        // Validasi secret key
        $expectedSecret = config('app.print_secret', 'default-print-secret');
        if ($request->header('X-Print-Secret') !== $expectedSecret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $validated = $request->validate([
            'error' => 'required|string'
        ]);
        
        $success = $this->markJobFailed($jobId, $validated['error']);
        
        if ($success) {
            Log::error("❌ Print job failed: {$jobId} - {$validated['error']}");
            return response()->json(['success' => true, 'message' => 'Job marked as failed']);
        } else {
            return response()->json(['success' => false, 'error' => 'Job not found'], 404);
        }
    });
    
    // Health check endpoint
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'Print Webhook',
            'timestamp' => now()->toISOString(),
            'pending_jobs' => count($this->getPendingPrintJobs())
        ]);
    });
});

// Helper functions
function savePrintJob(array $jobData): void
{
    $jobsFile = storage_path('app/print_jobs.json');
    $jobs = [];
    
    if (file_exists($jobsFile)) {
        $content = file_get_contents($jobsFile);
        $jobs = json_decode($content, true) ?? [];
    }
    
    $jobs[] = $jobData;
    file_put_contents($jobsFile, json_encode($jobs, JSON_PRETTY_PRINT));
}

function getPendingPrintJobs(): array
{
    $jobsFile = storage_path('app/print_jobs.json');
    
    if (!file_exists($jobsFile)) {
        return [];
    }
    
    $content = file_get_contents($jobsFile);
    $allJobs = json_decode($content, true) ?? [];
    
    return array_filter($allJobs, function ($job) {
        return $job['status'] === 'pending' && $job['attempts'] < 3;
    });
}

function markJobCompleted(string $jobId): bool
{
    return updateJobStatus($jobId, 'completed');
}

function markJobFailed(string $jobId, string $error): bool
{
    $jobsFile = storage_path('app/print_jobs.json');
    
    if (!file_exists($jobsFile)) {
        return false;
    }
    
    $content = file_get_contents($jobsFile);
    $jobs = json_decode($content, true) ?? [];
    $updated = false;
    
    foreach ($jobs as &$job) {
        if ($job['id'] === $jobId) {
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
}

function updateJobStatus(string $jobId, string $status): bool
{
    $jobsFile = storage_path('app/print_jobs.json');
    
    if (!file_exists($jobsFile)) {
        return false;
    }
    
    $content = file_get_contents($jobsFile);
    $jobs = json_decode($content, true) ?? [];
    $updated = false;
    
    foreach ($jobs as &$job) {
        if ($job['id'] === $jobId) {
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
}