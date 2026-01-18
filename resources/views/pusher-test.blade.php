<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusher Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-4">🧪 Pusher Connection Test</h1>

        <div id="status" class="mb-4 p-4 rounded bg-yellow-100 text-yellow-800">
            <strong>Status:</strong> Initializing...
        </div>

        <div class="mb-4">
            <button id="testBtn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Send Test Event
            </button>
        </div>

        <div id="log" class="bg-gray-50 p-4 rounded border border-gray-200 h-64 overflow-y-auto">
            <p class="text-gray-500">Waiting for events...</p>
        </div>
    </div>

    <script>
        const statusDiv = document.getElementById('status');
        const logDiv = document.getElementById('log');
        const testBtn = document.getElementById('testBtn');

        function addLog(message, type = 'info') {
            const time = new Date().toLocaleTimeString();
            const color = type === 'success' ? 'text-green-600' : type === 'error' ? 'text-red-600' : 'text-blue-600';
            logDiv.innerHTML += `<p class="${color}">[${time}] ${message}</p>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        // Wait for Echo to be initialized (it loads asynchronously)
        setTimeout(() => {
            // Check if Echo is defined
            if (typeof Echo !== 'undefined') {
                statusDiv.className = 'mb-4 p-4 rounded bg-green-100 text-green-800';
                statusDiv.innerHTML = '<strong>Status:</strong> ✅ Echo loaded successfully!';
                addLog('Echo is defined and ready', 'success');

                // Check Pusher connection
                if (window.Pusher) {
                    addLog('Pusher library loaded', 'success');
                }

                // Listen to test channel
                Echo.channel('test-channel')
                    .listen('.test-event', (e) => {
                        addLog('📨 Event received: ' + JSON.stringify(e), 'success');
                        alert('✅ Pusher Working! Message: ' + e.message);
                    });

                addLog('Listening to test-channel...', 'info');

                // Test button
                testBtn.addEventListener('click', async () => {
                    testBtn.disabled = true;
                    testBtn.textContent = 'Sending...';

                    try {
                        const response = await fetch('/test-pusher');
                        const data = await response.json();
                        addLog('Event sent to server: ' + data.message, 'success');
                    } catch (error) {
                        addLog('Error: ' + error.message, 'error');
                    } finally {
                        testBtn.disabled = false;
                        testBtn.textContent = 'Send Test Event';
                    }
                });

            } else {
                statusDiv.className = 'mb-4 p-4 rounded bg-red-100 text-red-800';
                statusDiv.innerHTML = '<strong>Status:</strong> ❌ Echo is not defined!';
                addLog('ERROR: Echo is not defined. Check if bootstrap.js is loaded.', 'error');
                addLog('Check browser console for errors', 'error');
            }
        }, 500); // Wait 500ms for Echo to initialize
    </script>
</body>

</html>