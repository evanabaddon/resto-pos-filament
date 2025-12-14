<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - {{ $record->employee->name }} - {{ $record->month_year }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-8 print:p-0 print:bg-white">

    @php
        $settings = app(App\Settings\GeneralSettings::class);
    @endphp

    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden print:shadow-none">

        <!-- Header -->
        <div class="px-8 py-6 border-b-2 border-gray-200 flex justify-between items-center">
            <div class="flex items-center gap-4">
                @if($settings->app_logo)
                    <img src="{{ asset('storage/' . $settings->app_logo) }}" alt="Logo" class="h-16 w-auto object-contain">
                @endif
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $settings->app_name ?? 'RESTO POS' }}</h1>
                    <p class="text-sm text-gray-500 max-w-[250px]">
                        {{ $settings->company_address ?? 'Alamat Belum Diatur' }}
                    </p>
                    <p class="text-sm text-gray-500">
                        {{ $settings->company_phone ? 'Telp: ' . $settings->company_phone : '' }}
                        {{ $settings->company_email ? '| Email: ' . $settings->company_email : '' }}
                    </p>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-blue-600 uppercase tracking-wide">SLIP GAJI</h2>
                <p class="text-sm text-gray-500 mt-1">Periode:
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $record->month_year)->translatedFormat('F Y') }}
                </p>
                <p class="text-sm text-gray-400">ID: #{{ str_pad($record->id, 6, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="px-8 py-6 bg-gray-50 border-b border-gray-200">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Nama Pegawai</span>
                    <p class="text-lg font-bold text-gray-800">{{ $record->employee->name }}</p>
                    <p class="text-sm text-gray-500">{{ $record->employee->position ?? 'Staff' }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Status Pembayaran</span>
                    <div class="mt-1">
                        @if($record->status === 'paid')
                            <span
                                class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase">LUNAS
                                (PAID)</span>
                        @else
                            <span
                                class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-bold uppercase">DRAFT</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="px-8 py-4 grid grid-cols-2 gap-3 text-center border-b border-gray-100">
            <div>
                <span class="block text-xs text-gray-500">Kehadiran</span>
                <span class="block font-bold text-gray-800">{{ $record->total_attendance_days }} Hari</span>
                @php
                    $d = is_array($record->details) ? $record->details : json_decode(json_encode($record->details), true);
                    $sick = $d['sick_days'] ?? 0;
                    $perm = $d['permission_days'] ?? 0;
                    $paidL = $d['paid_leave_days'] ?? 0;
                @endphp
                @if($sick > 0 || $perm > 0 || $paidL > 0)
                    <div class="mt-2 text-xs text-gray-600 bg-blue-50 rounded p-2 text-left space-y-1">
                        @if($sick > 0)
                            <div class="flex justify-between"><span>Sakit:</span> <span class="font-semibold">{{ $sick }}
                                    hari</span></div>
                        @endif
                        @if($perm > 0)
                            <div class="flex justify-between"><span>Izin:</span> <span class="font-semibold">{{ $perm }}
                                    hari</span> <span class="text-xxs text-red-500">(Unpaid)</span></div>
                        @endif
                        @if($paidL > 0)
                            <div class="flex justify-between"><span>Cuti:</span> <span class="font-semibold">{{ $paidL }}
                                    hari</span></div>
                        @endif
                    </div>
                @endif
            </div>
            <div>
                <span class="block text-xs text-gray-500">Lembur</span>
                <span class="block font-bold text-gray-800">{{ floor($record->total_overtime_minutes / 60) }} jam
                    {{ $record->total_overtime_minutes % 60 }} menit</span>
            </div>
        </div>

        <!-- Details Table -->
        <div class="px-8 py-6">
            <!-- Earnings -->
            <div class="mb-6">
                <h3 class="text-sm font-bold text-gray-700 uppercase border-b border-gray-300 pb-2 mb-3">Pendapatan</h3>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Gaji Pokok</span>
                    <span class="font-medium text-gray-800">Rp
                        {{ number_format($record->base_salary, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Uang Lembur</span>
                    <span class="font-medium text-gray-800">Rp
                        {{ number_format($record->overtime_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Deductions -->
            <div class="mb-6">
                <h3 class="text-sm font-bold text-red-700 uppercase border-b border-gray-300 pb-2 mb-3">Potongan</h3>
                @php
                    $details = $record->details;
                    if (!is_array($details)) {
                        $details = json_decode(json_encode($details), true) ?? [];
                    }
                    $breakdown = $details['deduction_details'] ?? [];
                @endphp

                @if(!empty($breakdown) && count($breakdown) > 0)
                    @foreach($breakdown as $name => $amount)
                        <div class="flex justify-between py-2 border-b border-gray-100 text-sm">
                            <span class="text-gray-600">{{ $name }}</span>
                            <span class="text-red-500 font-medium">- Rp {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                @else
                    @if(data_get($details, 'late_count', 0) > 0)
                        <div class="flex justify-between py-2 border-b border-gray-100 text-sm">
                            <span class="text-gray-600">Keterlambatan ({{ data_get($details, 'late_count') }}x)</span>
                            <span class="text-red-500 text-xs italic">(Di Total)</span>
                        </div>
                    @endif
                    @if(data_get($details, 'early_leave_count', 0) > 0)
                        <div class="flex justify-between py-2 border-b border-gray-100 text-sm">
                            <span class="text-gray-600">Pulang Cepat ({{ data_get($details, 'early_leave_count') }}x)</span>
                            <span class="text-red-500 text-xs italic">(Di Total)</span>
                        </div>
                    @endif
                @endif

                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600 font-medium">Total Potongan</span>
                    <span class="font-medium text-red-600">- Rp
                        {{ number_format($record->deductions, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Net Pay -->
            <div class="flex justify-between items-center bg-gray-100 p-4 rounded-lg">
                <span class="text-lg font-bold text-gray-700">TOTAL DITERIMA (TAKE HOME PAY)</span>
                <span class="text-2xl font-bold text-green-700">Rp
                    {{ number_format($record->total_payout, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Footer / Signature -->
        <div class="px-8 py-8 mt-4 grid grid-cols-2 gap-12 text-center text-sm">
            <div>
                <p class="mb-16">Penerima,</p>
                <p class="font-bold border-t border-gray-300 pt-2">{{ $record->employee->name }}</p>
            </div>
            <div>
                <p class="mb-16">HRD Manager,</p>
                <p class="font-bold border-t border-gray-300 pt-2">Admin Resto</p>
            </div>
        </div>

        <div class="text-center text-xs text-gray-400 mt-8 mb-4 print:hidden">
            <button onclick="window.print()"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow-lg transition">
                Cetak Slip (Print)
            </button>
        </div>
    </div>

</body>

</html>