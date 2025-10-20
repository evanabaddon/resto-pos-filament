<div 
    x-data="{
        visible: $wire.entangle('visible'),
        type: $wire.entangle('type'),
        playSound() {
            let sound;
            switch (this.type) {
                case 'success':
                    sound = new Audio('/sounds/success.wav');
                    break;
                case 'error':
                    sound = new Audio('/sounds/error.wav');
                    break;
                case 'warning':
                    sound = new Audio('/sounds/error.wav');
                    break;
                default:
                    sound = new Audio('/sounds/error.wav');
                    break;
            }
            sound.play();
        }
    }"
    x-init="
        $wire.on('hide-notification', e => {
            setTimeout(() => visible = false, e.timeout || 000)
        });
        $watch('visible', value => {
            if (value) playSound();
        });
    "
    x-show="visible"
    class="fixed inset-0 flex items-center justify-center z-[9999]"
    style="display: none;"
>
    <div 
        class="px-6 py-4 rounded-xl shadow-2xl text-white text-base font-semibold flex items-center space-x-3"
        :class="{
            'bg-green-600': type === 'success',
            'bg-red-600': type === 'error',
            'bg-yellow-500': type === 'warning',
            'bg-blue-600': type === 'info'
        }"
    >
        <template x-if="type === 'success'"><span>✅</span></template>
        <template x-if="type === 'error'"><span>❌</span></template>
        <template x-if="type === 'warning'"><span>⚠️</span></template>
        <template x-if="type === 'info'"><span>ℹ️</span></template>

        <span x-text="$wire.message"></span>
    </div>
</div>
