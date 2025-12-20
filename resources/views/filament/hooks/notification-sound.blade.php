<script>
    document.addEventListener('livewire:initialized', () => {
        let previousCount = 0;

        function checkCount() {
            // Target the database notifications button in the topbar
            const btn = document.querySelector('.fi-topbar-database-notifications-btn, [x-bind\\:class*=\'databaseNotifications\']');
            if (!btn) return;

            // Find valid badge inside
            const potentialBadges = btn.querySelectorAll('span');
            let count = 0;

            potentialBadges.forEach(span => {
                const txt = span.innerText.trim();
                // Filter out non-numeric and large numbers (timestamps etc)
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
            audio.play().then(() => console.log('Sound played')).catch(e => console.error('Sound error:', e));
        }

        // Check initially
        setTimeout(checkCount, 1000);

        // Hook into Livewire updates to detect notification changes
        Livewire.hook('morph.updated', ({
            el,
            component
        }) => {
            checkCount();
        });

        // Also keep a slow polling just in case
        setInterval(checkCount, 3000);
    });
</script>