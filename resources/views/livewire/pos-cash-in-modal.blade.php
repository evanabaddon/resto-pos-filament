<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            {{-- Background blur biru --}}
            <div class="absolute inset-0 bg-gray-900/80" wire:click="cancelCashIn"></div>

            {{-- Modal box --}}
            <div
                class="relative bg-white rounded-xl shadow-2xl w-80 p-6 flex flex-col items-stretch space-y-4 animate-fade-in">

                {{-- Header --}}
                <h2 class="text-lg font-semibold text-gray-800 text-center">{{ __('messages.cash_in_title') }}</h2>
                <p class="text-sm text-gray-600 text-center">
                    {{ __('messages.cash_in_desc') }}
                </p>

                {{-- Input --}}
                <input type="number" wire:model="cashInHand" wire:keydown.enter="confirmCashIn"
                    class="w-full border border-gray-300 rounded-lg text-right px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:outline-none"
                    placeholder="{{ __('messages.cash_in_placeholder') }}" />

                {{-- Tombol --}}
                <div class="flex justify-end space-x-2 mt-4">
                    <button wire:click="cancelCashIn"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition cursor-pointer">
                        {{ __('messages.cancel') }}
                    </button>

                    <button wire:click="confirmCashIn"
                        class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium shadow-md transition transform hover:scale-105 cursor-pointer">
                        {{ __('messages.save') }}
                    </button>
                </div>
            </div>
        </div>

        <style>
            @keyframes fade-in {
                0% {
                    opacity: 0;
                    transform: translateY(-10px);
                }

                100% {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-fade-in {
                animation: fade-in 0.25s ease-out forwards;
            }
        </style>
    @endif
</div>