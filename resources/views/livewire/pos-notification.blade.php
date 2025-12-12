<div x-data="{ 
    notifications: [],
    add(message, type = 'success') {
        if (!message) return;
        const id = Date.now();
        this.notifications.push({ id, message, type });
        this.playSound(type);
        setTimeout(() => this.remove(id), 3500);
    },
    remove(id) {
        const index = this.notifications.findIndex(n => n.id === id);
        if (index > -1) {
            this.notifications.splice(index, 1);
        }
    },
    playSound(type) {
        if (typeof PosSound !== 'undefined') {
            PosSound.play(type);
        }
    }
}"
x-on:show-notification.window="
    console.log('🔔 Notification Event:', $event.detail);
    // Livewire 3 named arguments dispatch: $event.detail.message
    let msg = $event.detail.message || $event.detail[0];
    let type = $event.detail.type || $event.detail[1] || 'success';
    add(msg, type);
"
class="fixed top-6 left-1/2 transform -translate-x-1/2 z-[9999] flex flex-col gap-3 w-full max-w-sm px-4 pointer-events-none">

<template x-for="note in notifications" :key="note.id">
    <div x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
         class="pointer-events-auto flex items-center p-4 rounded-2xl shadow-xl border backdrop-blur-xl hover:scale-[1.02] transition-transform"
         :class="{
            'bg-white/95 border-emerald-100 text-emerald-800 shadow-emerald-100/50': note.type === 'success',
            'bg-white/95 border-rose-100 text-rose-800 shadow-rose-100/50': note.type === 'error',
            'bg-white/95 border-blue-100 text-blue-800 shadow-blue-100/50': note.type === 'info',
            'bg-white/95 border-amber-100 text-amber-800 shadow-amber-100/50': note.type === 'warning'
         }">
        
        {{-- Icon --}}
        <div class="flex-shrink-0 mr-3">
            <template x-if="note.type === 'success'">
                <div class="w-8 h-8 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-full flex items-center justify-center shadow-inner">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
            </template>
            <template x-if="note.type === 'error'">
                <div class="w-8 h-8 bg-gradient-to-br from-rose-100 to-rose-200 rounded-full flex items-center justify-center shadow-inner">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            </template>
            <template x-if="note.type === 'info'">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-blue-200 rounded-full flex items-center justify-center shadow-inner">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </template>
            <template x-if="note.type === 'warning'">
                <div class="w-8 h-8 bg-gradient-to-br from-amber-100 to-amber-200 rounded-full flex items-center justify-center shadow-inner">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </template>
        </div>

        {{-- Message --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-black leading-tight tracking-tight" x-text="note.type.charAt(0).toUpperCase() + note.type.slice(1)"></p>
            <p class="text-xs font-medium mt-0.5 opacity-90 truncate leading-relaxed" x-text="note.message"></p>
        </div>

        {{-- Close Button --}}
        <button @click="remove(note.id)" class="ml-2 w-6 h-6 flex items-center justify-center rounded-full hover:bg-black/5 text-slate-400 hover:text-slate-600 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</template>
</div>
