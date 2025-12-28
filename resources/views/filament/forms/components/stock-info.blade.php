@php
$state = $getState();
@endphp

<div>
    @if($state)
    <div class="fi-fo-field-wrp">
        <div class="grid gap-y-2">
            <div class="flex items-center justify-between gap-x-3">
                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                        Info Stok & Estimasi
                    </span>
                </label>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2">
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $state }}</p>
            </div>
        </div>
    </div>
    @endif
</div>