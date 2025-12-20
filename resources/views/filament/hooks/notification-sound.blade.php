<script>
    document.addEventListener('livewire:initialized', () => {
        let previousCount = 0;
        let isAudioUnlocked = false;

        // Trik Audio Unlocker: Mainkan suara tanpa volume pada interaksi pertama
        const unlockAudio = () => {
            if (isAudioUnlocked) return;

            const audio = new Audio('/sounds/whatsapp_pc.mp3');
            audio.volume = 0;
            audio.play()
                .then(() => {
                    isAudioUnlocked = true;
                    console.log('🔊 Audio Context Unlocked: Notifikasi suara siap beraksi.');
                    document.removeEventListener('click', unlockAudio);
                    document.removeEventListener('keydown', unlockAudio);
                })
                .catch(err => {
                    // Masih terkunci, biarkan listener tetap aktif
                });
        };

        // Pasang listener pada seluruh dokumen
        document.addEventListener('click', unlockAudio);
        document.addEventListener('keydown', unlockAudio);

        function checkCount() {
            // Target tombol notifikasi di topbar Filament
            const btn = document.querySelector('.fi-topbar-database-notifications-btn, [x-bind\\:class*=\'databaseNotifications\']');
            if (!btn) return;

            const potentialBadges = btn.querySelectorAll('span');
            let count = 0;

            potentialBadges.forEach(span => {
                const txt = span.innerText.trim();
                if (txt && /^\d+$/.test(txt) && txt.length < 5) {
                    count = parseInt(txt);
                }
            });

            if (count > previousCount) {
                console.log('Notification count increased to', count);
                playSound();
            }
            previousCount = count;
        }

        function playSound() {
            const audio = new Audio('/sounds/whatsapp_pc.mp3');
            audio.play()
                .then(() => console.log('✅ Notifikasi berbunyi'))
                .catch(e => {
                    if (e.name === 'NotAllowedError') {
                        console.warn('⚠️ Suara diblokir browser. Klik di mana saja pada halaman untuk mengaktifkan.');
                    } else {
                        console.error('❌ Gagal memutar suara:', e);
                    }
                });
        }

        // Jalankan pengecekan rutin
        setTimeout(checkCount, 1000);

        Livewire.hook('morph.updated', ({
            el,
            component
        }) => {
            checkCount();
        });

        setInterval(checkCount, 3000);
    });
</script>