<?php

namespace App\Filament\Clusters\Hrm\Pages;

use App\Filament\Clusters\Hrm\HrmCluster;
use App\Models\Attendance;
use App\Models\Employee;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttendanceMachine extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    protected string $view = 'filament.clusters.hrm.pages.attendance-machine';

    protected static ?string $cluster = HrmCluster::class;

    protected static ?string $navigationLabel = 'Mesin Absensi';

    protected static ?string $title = 'Mesin Absensi Wajah';

    // Disable navigation if needed, or keep it for admin access
    // protected static bool $shouldRegisterNavigation = true;
    public $allEmployees = [];

    public function mount()
    {
        $todayStr = now()->toDateString();

        // Load all employees that have descriptors
        $this->allEmployees = Employee::whereNotNull('face_descriptor')
            ->get(['id', 'name', 'face_descriptor'])
            ->map(function ($employee) use ($todayStr) {
                // Check today's attendance
                $attendance = Attendance::where('employee_id', $employee->id)
                    ->where('date', $todayStr)
                    ->first();

                $status = 'none';
                if ($attendance) {
                    if ($attendance->clock_out) {
                        $status = 'checked_out';
                    } elseif ($attendance->clock_in) {
                        $status = 'checked_in';
                    }
                }

                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'descriptors' => $employee->face_descriptor,
                    'today_status' => $status, // none, checked_in, checked_out
                ];
            })
            ->toArray();
    }

    public function clockIn($faceDescriptor, $snapshot)
    {
        $employee = $this->findEmployeeByFace($faceDescriptor);

        if (!$employee) {
            $this->dispatch('attendance-error', message: 'Wajah tidak dikenali!');
            return;
        }

        // 1. Check if already clocked in today
        $existing = Attendance::where('employee_id', $employee->id)
            ->where('date', now()->toDateString())
            ->first();

        if ($existing) {
            $this->dispatch('attendance-error', message: "Halo {$employee->name}, Anda sudah melakukan absen hari ini pukul " . $existing->clock_in->format('H:i'));
            return;
        }

        // 2. Check Late Status
        $isLate = false;
        $statusMessage = '';

        // Ensure shift relation is loaded
        $employee->load('shift');

        if ($employee->shift) {
            try {
                $shiftStart = \Carbon\Carbon::parse($employee->shift->start_time);
                // We use 'today' combined with shift time to compare properly
                $shiftStartDateTime = now()->setTimeFrom($shiftStart);

                // Add grace period 15 mins
                $lateThreshold = $shiftStartDateTime->copy()->addMinutes(15);

                if (now()->gt($lateThreshold)) {
                    $isLate = true;
                    $statusMessage = ' (Terlambat - Shift: ' . $employee->shift->name . ')';
                } else {
                    $statusMessage = ' (Tepat Waktu - Shift: ' . $employee->shift->name . ')';
                }
            } catch (\Exception $e) {
                // Time parsing error fallback
            }
        }

        // Save snapshot
        $image_parts = explode(";base64,", $snapshot);
        $image_base64 = base64_decode($image_parts[1]);
        $fileName = 'attendances/' . Str::random(40) . '.png';
        Storage::disk('public')->put($fileName, $image_base64);

        // Record Attendance
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => $isLate ? 'late' : 'present',
            'is_late' => $isLate,
            'snapshot_path' => $fileName,
        ]);

        $this->dispatch('attendance-success', message: "Berhasil Clock In: {$employee->name}{$statusMessage}");
    }

    public function clockOut($faceDescriptor, $snapshot)
    {
        $employee = $this->findEmployeeByFace($faceDescriptor);

        if (!$employee) {
            $this->dispatch('attendance-error', message: 'Wajah tidak dikenali!');
            return;
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->first();

        if (!$attendance) {
            $this->dispatch('attendance-error', message: 'Anda belum Clock In atau sudah Clock Out!');
            return;
        }

        // Check Early Leave / Overtime
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
                        $statusMessage = ' (Overtime)';
                    }
                }
            } catch (\Exception $e) {
                // Time parsing error
            }
        }

        $attendance->update([
            'clock_out' => now(),
            'is_early_leave' => $isEarlyLeave,
            'overtime_minutes' => $overtimeMinutes,
        ]);

        $this->dispatch('attendance-success', message: "Berhasil Clock Out: {$employee->name}{$statusMessage}");
    }

    protected function findEmployeeByFace($descriptor)
    {
        // This is a simplified Euclidean distance check on server side? 
        // OR we trust the client side ID? 
        // For security, usually we match on server. But PHP matching is slow/hard without extension.
        // PLAN: The frontend finds the match and sends matches ID. 
        // BUT for higher security, we should store descriptors in DB and compare. 
        // Given constraints and typical easy-setup requirements:
        // OPTION A: Frontend sends "Employee ID" it matched. (Less secure but fast and easy)
        // OPTION B: Frontend sends descriptor, Backend iterates all employees and calc distance. (Secure-ish, slow if many employees)

        // For this demo/MVP, let's implement Option B (Backend matching) to handle "Security".
        // Distance threshold typically 0.6

        $employees = Employee::whereNotNull('face_descriptor')->get();
        $bestMatch = null;
        $lowestDistance = 1.0;

        foreach ($employees as $employee) {
            $storedDescriptors = $employee->face_descriptor; // This is array of array (if multiple photos) or single array?
            // Usually we store one representative descriptor or array of them.
            // Let's assume we store array of descriptors (from multiple photos).

            if (empty($storedDescriptors))
                continue;

            // If stored is simple array (one face), wrap it
            if (!is_array($storedDescriptors[0])) {
                $storedDescriptors = [$storedDescriptors];
            }

            foreach ($storedDescriptors as $stored) {
                $distance = $this->euclideanDistance($descriptor, $stored);
                if ($distance < 0.6 && $distance < $lowestDistance) {
                    $lowestDistance = $distance;
                    $bestMatch = $employee;
                }
            }
        }

        return $bestMatch;
    }

    protected function euclideanDistance($a, $b)
    {
        $sum = 0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += pow($a[$i] - $b[$i], 2);
        }
        return sqrt($sum);
    }
}
