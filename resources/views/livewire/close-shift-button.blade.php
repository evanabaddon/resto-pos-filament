<div>
    <!-- Tombol Tutup Shift -->
    <button 
        wire:click="openConfirmationModal"
        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm font-medium cursor-pointer transition-colors duration-200">
        Tutup Shift
    </button>

    <!-- Modal Konfirmasi -->
    @if($showConfirmationModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-md bg-opacity-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Konfirmasi Tutup Shift
                    </h3>
                </div>

                <!-- Body -->
                <div class="px-6 py-4">
                    <p class="text-gray-700">
                        Apakah Anda yakin ingin menutup shift kasir saat ini?
                    </p>
                    <p class="text-sm text-gray-500 mt-2">
                        Setelah shift ditutup, Anda tidak dapat melakukan transaksi lagi sampai membuka shift baru.
                    </p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end space-x-3">
                    <button 
                        wire:click="closeConfirmationModal"
                        class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                        Batal
                    </button>
                    <button 
                        wire:click="closeShift"
                        class="cursor-pointer px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                        Tutup Shift
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>