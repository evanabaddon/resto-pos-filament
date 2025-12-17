<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Employee;
use App\Services\AttendanceService;
use Filament\Notifications\Notification;

class AttendanceKiosk extends Component
{
    public $currentTime = '';
    public $currentDate = '';

    // Logic Variables
    public $allEmployees = [];
    public $recognitionActive = true;
    public $isProcessing = false;

    public function mount()
    {
        $this->currentTime = now()->format('H:i:s');
        $this->currentDate = now()->locale('id')->translatedFormat('l, d F Y');

        $this->loadEmployeesForRecognition();
    }

    protected function loadEmployeesForRecognition()
    {
        $todayStr = now()->toDateString();

        // Load all employees that have descriptors
        $this->allEmployees = Employee::where('status', 'active')
            ->whereNotNull('face_descriptor')
            ->get(['id', 'name', 'face_descriptor', 'photo_path'])
            ->map(function ($employee) use ($todayStr) {
                // Check today's attendance
                $attendance = \App\Models\Attendance::where('employee_id', $employee->id)
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
                    'photo' => $employee->photo_path ? \Illuminate\Support\Facades\Storage::url($employee->photo_path) : null,
                    'today_status' => $status,
                ];
            })
            ->toArray();
    }

    public function handleFaceDetected($params)
    {
        // params: { descriptor: [], snapshot: 'base64...' }
        // Note: For security, we match on server.
        $descriptor = $params['descriptor'] ?? [];
        $snapshot = $params['snapshot'] ?? null;
        $mode = $params['mode'] ?? 'check'; // check, in, out

        if (empty($descriptor))
            return;

        $employee = $this->findEmployeeByFace($descriptor);

        if (!$employee) {
            $this->dispatch('play-sound', type: 'error');
            // Custom notification event
            $this->dispatch('show-notification', message: 'Wajah tidak dikenali!', type: 'error');
            return;
        }

        // Logic check
        if ($mode === 'in') {
            $this->processClockIn($employee, $snapshot);
        } elseif ($mode === 'out') {
            $this->processClockOut($employee, $snapshot);
        } else {
            // Just verify / show name
            $this->dispatch('face-verified', [
                'id' => $employee->id,
                'name' => $employee->name,
                'today_status' => $this->getEmployeeStatus($employee->id)
            ]);
        }
    }

    protected function getEmployeeStatus($id)
    {
        $emp = collect($this->allEmployees)->firstWhere('id', $id);
        return $emp['today_status'] ?? 'none';
    }

    public function processClockIn($employeeData, $snapshot)
    {
        // Use Employee Model
        $employee = Employee::find($employeeData->id);
        $service = new AttendanceService();
        $result = $service->clockIn($employee, $snapshot);

        if ($result['success']) {
            $this->dispatch('play-sound', type: 'success');
            $this->dispatch('show-notification', message: $result['message'], type: 'success');

            // Refresh local state
            $this->loadEmployeesForRecognition();
            $this->dispatch('face-verified', [
                'id' => $employee->id,
                'name' => $employee->name,
                'today_status' => 'checked_in'
            ]);
        } else {
            $this->dispatch('play-sound', type: 'warning');
            $this->dispatch('show-notification', message: $result['message'], type: 'warning');
        }
    }

    public function processClockOut($employeeData, $snapshot)
    {
        $employee = Employee::find($employeeData->id);
        $service = new AttendanceService();
        $result = $service->clockOut($employee, $snapshot);

        if ($result['success']) {
            $this->dispatch('play-sound', type: 'success');
            $this->dispatch('show-notification', message: $result['message'], type: 'success');

            $this->loadEmployeesForRecognition();
            $this->dispatch('face-verified', [
                'id' => $employee->id,
                'name' => $employee->name,
                'today_status' => 'checked_out'
            ]);
        } else {
            $this->dispatch('play-sound', type: 'warning');
            $this->dispatch('show-notification', message: $result['message'], type: 'warning');
        }
    }

    protected function findEmployeeByFace($descriptor)
    {
        $employees = Employee::whereNotNull('face_descriptor')->get();
        $bestMatch = null;
        $lowestDistance = 1.0;

        foreach ($employees as $employee) {
            $storedDescriptors = $employee->face_descriptor;
            if (empty($storedDescriptors))
                continue;

            // Ensure array of arrays
            if (!is_array($storedDescriptors))
                continue;

            if (isset($storedDescriptors[0]) && !is_array($storedDescriptors[0])) {
                $storedDescriptors = [$storedDescriptors];
            }

            foreach ($storedDescriptors as $stored) {
                // Check dimension matching
                if (count($stored) !== count($descriptor))
                    continue;

                $distance = $this->euclideanDistance($descriptor, $stored);
                // STRICT THRESHOLD 0.45
                if ($distance < 0.45 && $distance < $lowestDistance) {
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
        $count = count($a);
        for ($i = 0; $i < $count; $i++) {
            $sum += pow($a[$i] - $b[$i], 2);
        }
        return sqrt($sum);
    }

    public function render()
    {
        // Re-use the existing view
        return view('filament.pages.attendance-kiosk')->layout('layouts.pos-layout', ['title' => 'Absensi Kiosk']);
    }
}
