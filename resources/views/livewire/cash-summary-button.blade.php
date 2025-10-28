<div class="hidden sm:block">
    <button id="cash-summary-btn"
        wire:click="openCashSummary"
        @class([
            'cursor-pointer flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
            'bg-blue-500 hover:bg-blue-600 text-white shadow-sm' => $hasActiveSession,
            'bg-gray-300 text-gray-500 cursor-not-allowed' => !$hasActiveSession
        ])
        @if(!$hasActiveSession) disabled @endif>
        
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01m12-.01a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Cash Summary</span>
    </button>
</div>
