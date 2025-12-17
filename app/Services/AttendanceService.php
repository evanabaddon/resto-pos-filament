<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttendanceService
{
    /**
     * Handle Clock In
     */
    public function clockIn(Employee $employee, $photoData)
    {
        // 1. Check if already clocked in today
        $today = now()->format('Y-m-d');
        $existing = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'Anda sudah absen masuk hari ini.'];
        }

        // 2. Save Photo
        $photoPath = $this->savePhoto($photoData, 'in');

        // 3. Check Late Status
        $isLate = false;
        $statusMessage = '';

        $employee->load('shift');

        if ($employee->shift) {
            try {
                $shiftStart = \Carbon\Carbon::parse($employee->shift->start_time);
                $shiftStartDateTime = now()->setTimeFrom($shiftStart);

                // Add grace period 15 mins
                $lateThreshold = $shiftStartDateTime->copy()->addMinutes(15);

                if (now()->gt($lateThreshold)) {
                    $isLate = true;
                    $statusMessage = ' (Terlambat)';
                } else {
                    $statusMessage = ' (Tepat Waktu)';
                }
            } catch (\Exception $e) {
                // Time parsing error
            }
        }

        // 4. Create Record
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'date' => $today,
            'clock_in' => now(),
            'photo_in_path' => $photoPath,
            'status' => $isLate ? 'late' : 'present',
            'is_late' => $isLate,
        ]);

        return ['success' => true, 'message' => "Selamat Pagi! Absen masuk berhasil{$statusMessage}.", 'data' => $attendance];
    }

    /**
     * Handle Clock Out
     */
    public function clockOut(Employee $employee, $photoData)
    {
        $today = now()->format('Y-m-d');
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return ['success' => false, 'message' => 'Anda belum absen masuk hari ini.'];
        }

        if ($attendance->clock_out) {
            return ['success' => false, 'message' => 'Anda sudah absen pulang hari ini.'];
        }

        // Save Photo
        $photoPath = $this->savePhoto($photoData, 'out');

        // Calculate Work Duration / Overtime
        $isEarlyLeave = false;
        $overtimeMinutes = 0;
        $statusMessage = '';

        $employee->load('shift');

        if ($employee->shift) {
            try {
                $shiftEnd = \Carbon\Carbon::parse($employee->shift->end_time);
                $shiftEndDateTime = now()->setTimeFrom($shiftEnd);

                if (now()->lessThan($shiftEndDateTime)) {
                    $isEarlyLeave = true;
                    $statusMessage = ' (Pulang Cepat)';
                } else {
                    $overtimeMinutes = now()->diffInMinutes($shiftEndDateTime);
                    if ($overtimeMinutes > 60) {
                        $statusMessage = ' (Lembur ' . floor($overtimeMinutes / 60) . ' jam)';
                    }
                }
            } catch (\Exception $e) {
                // Time parsing error
            }
        }

        // Update Record
        $attendance->update([
            'clock_out' => now(),
            'photo_out_path' => $photoPath,
            'is_early_leave' => $isEarlyLeave,
            'overtime_minutes' => $overtimeMinutes,
        ]);

        return ['success' => true, 'message' => "Hati-hati di jalan! Absen pulang berhasil{$statusMessage}.", 'data' => $attendance];
    }

    /**
     * Decode base64 and save to storage
     */
    private function savePhoto($base64Data, $type)
    {
        if (!$base64Data)
            return null;

        // Remove header if present (data:image/png;base64,...)
        if (strpos($base64Data, 'base64,') !== false) {
            $base64Data = explode('base64,', $base64Data)[1];
        }

        $imageData = base64_decode($base64Data);
        $fileName = 'attendance/' . date('Y-m-d') . '/' . $type . '_' . Str::random(10) . '.jpg';

        Storage::disk('public')->put($fileName, $imageData);

        return $fileName;
    }
}
