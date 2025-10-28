<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('/welcome');
});

Route::post('/webhook/print', function (Request $request) {
    \Log::info('Print webhook received', $request->all());
    
    // Validasi secret key
    if ($request->header('X-Print-Secret') !== config('app.print_secret')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    // Simpan print job ke database atau file
    $jobId = uniqid();
    $printData = [
        'id' => $jobId,
        'content' => $request->content,
        'printer' => $request->printer ?? 'BAR',
        'created_at' => now()->toISOString(),
        'status' => 'pending'
    ];
    
    // Simpan ke file (simple)
    $jobs = storage_path('app/print_jobs.json');
    file_put_contents($jobs, json_encode($printData) . "\n", FILE_APPEND);
    
    return response()->json([
        'success' => true,
        'job_id' => $jobId,
        'message' => 'Print job queued'
    ]);
});

// Endpoint untuk Windows client mengambil jobs
Route::get('/webhook/print-jobs', function () {
    $jobsFile = storage_path('app/print_jobs.json');
    
    if (!file_exists($jobsFile)) {
        return response()->json(['jobs' => []]);
    }
    
    $jobs = file($jobsFile);
    $pendingJobs = [];
    
    foreach ($jobs as $job) {
        $jobData = json_decode(trim($job), true);
        if ($jobData['status'] === 'pending') {
            $pendingJobs[] = $jobData;
        }
    }
    
    return response()->json(['jobs' => $pendingJobs]);
});

// Endpoint untuk update status job
Route::post('/webhook/print-job/{jobId}/complete', function ($jobId) {
    $jobsFile = storage_path('app/print_jobs.json');
    
    if (file_exists($jobsFile)) {
        $jobs = file($jobsFile);
        $updatedJobs = [];
        
        foreach ($jobs as $job) {
            $jobData = json_decode(trim($job), true);
            if ($jobData['id'] === $jobId) {
                $jobData['status'] = 'completed';
                $jobData['completed_at'] = now()->toISOString();
            }
            $updatedJobs[] = json_encode($jobData);
        }
        
        file_put_contents($jobsFile, implode("\n", $updatedJobs));
    }
    
    return response()->json(['success' => true]);
});