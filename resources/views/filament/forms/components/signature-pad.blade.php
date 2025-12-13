<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            signaturePad: null,
            init() {
                let canvas = this.$refs.canvas;
                let context = canvas.getContext('2d');
                
                // Simple drawing logic
                let isDrawing = false;
                
                canvas.addEventListener('mousedown', (e) => {
                    isDrawing = true;
                    context.beginPath();
                    context.moveTo(e.offsetX, e.offsetY);
                });
                
                canvas.addEventListener('mousemove', (e) => {
                    if (isDrawing) {
                        context.lineTo(e.offsetX, e.offsetY);
                        context.stroke();
                    }
                });
                
                canvas.addEventListener('mouseup', () => {
                    isDrawing = false;
                    this.state = canvas.toDataURL();
                });
                
                canvas.addEventListener('touchstart', (e) => {
                    e.preventDefault(); // Prevent scrolling
                    let rect = canvas.getBoundingClientRect();
                    let touch = e.touches[0];
                    isDrawing = true;
                    context.beginPath();
                    context.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
                }, { passive: false });
                
                canvas.addEventListener('touchmove', (e) => {
                    e.preventDefault();
                    if (isDrawing) {
                        let rect = canvas.getBoundingClientRect();
                        let touch = e.touches[0];
                        context.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
                        context.stroke();
                    }
                }, { passive: false });
                
                canvas.addEventListener('touchend', () => {
                    isDrawing = false;
                    this.state = canvas.toDataURL();
                });

                if (this.state) {
                    let img = new Image();
                    img.onload = () => context.drawImage(img, 0, 0);
                    // If it's a data URL, use it directly. Otherwise, assume it's a storage path.
                    // We assume storage/ is the public prefix. Adjust if necessary.
                    if (this.state.startsWith('data:image')) {
                         img.src = this.state;
                    } else {
                         img.src = '/storage/' + this.state;
                    }
                }
            },
            clear() {
                let canvas = this.$refs.canvas;
                let context = canvas.getContext('2d');
                context.clearRect(0, 0, canvas.width, canvas.height);
                this.state = null;
            }
        }">
        <div class="border rounded-lg overflow-hidden border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
            <canvas x-ref="canvas" width="600" height="200" class="w-full touch-none" style="max-width: 100%;"></canvas>
        </div>
        <div class="mt-2 text-right">
            <x-filament::button size="sm" color="danger" x-on:click.prevent="clear">
                Hapus Tanda Tangan
            </x-filament::button>
        </div>
    </div>
</x-dynamic-component>