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
            const audio = new Audio('data:audio/mp3;base64,//uQRAAAAWMSLwUIYAAsYkXgoQwAEaYLWfkWgAI0wWs/ItAAAGDgYtAgAyN+QWaAAihwMWm4G8QQRDiMcCBcH3Cc+CDv/7cQBwl3qZzZ9UHadbDf8jX8m8q/rnM9847M7C3d/fu/5otj079znef7354Jt//X1z3eEc4gamDmnky5f/uQRAAL888jXywgAAHnka+WEAAAEIGbCU8AAAAAAAAAAAAAIAa5wsts+G1l569LO190qvGDP8SXqqI9dpYOTgIlU8G7i3/eDxbvCVEQKwJSLdZSzpk2EQjAdr/5qPCFxZph8thishZ8LOqd8dZh8vB92cP20Xm//uQRAAAAAAwAABAAAABAAABAAAOE1iWvh9Y7Rby7hzF8aRnziWsE+0UjvFh06Fm6qlvqx11T5Ze13W09l8kwLK8lI4kYvPc//7cRSS+0/1/fH566f/n8vr7+Mc80eLR1m101/F5ykds8/t//uQRAAAAAAwAABAAAABAAABAAACAe7v7/t//7/f/9/3//v9//7/f/9/3//v9//7/f/9/3//v9//7/f/9/3//v9//7/f/9/3//v9//7/f/9/3//v9//7/f/9/3//v9//7/f/9/3//v9//7/f/9//uQRAAAAAAwAABAAAABAAABAAAC');
            audio.play().then(() => console.log('Sound played')).catch(e => console.error('Sound error:', e));
        }

        // Check initially
        setTimeout(checkCount, 1000);

        // Hook into Livewire updates to detect notification changes
        Livewire.hook('morph.updated', ({ el, component }) => {
            checkCount();
        });

        // Also keep a slow polling just in case
        setInterval(checkCount, 3000);
    });
</script>