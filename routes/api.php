<?php

use App\Models\PrintJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhook routes tanpa CSRF protection (API routes)
Route::prefix('webhook')->group(function () {
    
    Route::post('/print', function (Request $request) {
        try {
            Log::info('🖨️ Print webhook received', $request->all());
            
            // Validasi secret key
            $expectedSecret = config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');
            
            if ($receivedSecret !== $expectedSecret) {
                Log::warning('❌ Unauthorized print webhook attempt');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $validated = $request->validate([
                'content' => 'required|string',
                'printer' => 'sometimes|string',
                'division' => 'sometimes|string',
                'sale_id' => 'sometimes|integer',
                'type' => 'sometimes|string|in:receipt,order,test'
            ]);

            $jobId = 'job_' . uniqid() . '_' . time();
            
            $printJob = PrintJob::create([
                'job_id' => $jobId,
                'content' => $validated['content'],
                'printer' => $validated['printer'] ?? 'BAR',
                'division' => $validated['division'] ?? 'general',
                'sale_id' => $validated['sale_id'] ?? null,
                'type' => $validated['type'] ?? 'order',
                'status' => 'pending',
                'attempts' => 0
            ]);
            
            Log::info("✅ Print job queued: {$jobId}");

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Print job queued successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Webhook print error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    });
    
    Route::get('/print-jobs', function (Request $request) {
        try {
            $expectedSecret = config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');
            
            if ($receivedSecret !== $expectedSecret) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $jobs = PrintJob::where('status', 'pending')
                           ->where('attempts', '<', 3)
                           ->get()
                           ->map(function ($job) {
                               return [
                                   'id' => $job->job_id,
                                   'content' => $job->content,
                                   'printer' => $job->printer,
                                   'division' => $job->division,
                                   'type' => $job->type,
                                   'sale_id' => $job->sale_id,
                                   'created_at' => $job->created_at->toISOString()
                               ];
                           })
                           ->toArray();
            
            return response()->json([
                'success' => true,
                'jobs' => $jobs,
                'total' => count($jobs)
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Get print jobs error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    });
    
    Route::post('/print-job/{jobId}/complete', function ($jobId, Request $request) {
        try {
            $expectedSecret = config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');
            
            if ($receivedSecret !== $expectedSecret) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $printJob = PrintJob::where('job_id', $jobId)->first();
            
            if ($printJob) {
                $printJob->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
                
                Log::info("✅ Print job completed: {$jobId}");
                return response()->json(['success' => true, 'message' => 'Job completed']);
            } else {
                return response()->json(['error' => 'Job not found'], 404);
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Complete job error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    });

    Route::post('/print-job/{jobId}/failed', function ($jobId, Request $request) {
        try {
            $expectedSecret = config('app.print_secret', 'default-print-secret-123');
            $receivedSecret = $request->header('X-Print-Secret');
            
            if ($receivedSecret !== $expectedSecret) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            $validated = $request->validate([
                'error' => 'required|string'
            ]);
            
            $printJob = PrintJob::where('job_id', $jobId)->first();
            
            if ($printJob) {
                $printJob->update([
                    'status' => 'failed',
                    'error' => $validated['error'],
                    'attempts' => $printJob->attempts + 1,
                    'completed_at' => now()
                ]);
                
                Log::info("❌ Print job failed: {$jobId}");
                return response()->json(['success' => true, 'message' => 'Job failed']);
            } else {
                return response()->json(['error' => 'Job not found'], 404);
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Failed job error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    });
    
    Route::get('/health', function () {
        try {
            $pendingJobs = PrintJob::where('status', 'pending')->count();
            
            return response()->json([
                'status' => 'ok',
                'pending_jobs' => $pendingJobs,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    });

    Route::get('/test', function () {
        return response()->json([
            'message' => 'Webhook API is running!',
            'timestamp' => now()->toISOString()
        ]);
    });
});