<div x-data="kioskData()" x-init="initKiosk()"
    class="flex flex-col items-center justify-start md:justify-center min-h-screen bg-slate-900 text-white relative overflow-y-auto md:overflow-hidden pt-6 md:pt-0">

    {{-- Background Effect --}}
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-900 via-slate-900 to-black opacity-80 z-0"></div>
    <div
        class="absolute inset-0 bg-[url('https://res.cloudinary.com/dflj4i4i4/image/upload/v1619717765/pattern-bg_z1.png')] opacity-10 z-0">
    </div>

    <div class="relative z-10 text-center space-y-2 md:space-y-8 w-full max-w-6xl px-4 py-2 md:py-0">

        {{-- Header & Clock --}}
        <div class="flex flex-col md:flex-row justify-between items-center w-full px-2 md:px-8 gap-2 md:gap-0">
            <div class="text-center md:text-left">
                <h1
                    class="text-xl md:text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400">
                    Mesin Absensi AI
                </h1>
                <p class="text-slate-400 text-[10px] md:text-sm" x-text="message"></p>
            </div>
            <div class="text-center md:text-right">
                <div class="text-xs md:text-xl font-light text-indigo-300 mb-0.5">{{ $currentDate }}</div>
                <div class="text-3xl md:text-5xl font-black tracking-tighter text-white font-mono" x-text="time">
                    {{ $currentTime }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-8 mt-2 md:mt-8 items-center h-full pb-10 md:pb-0">

            {{-- Left: Camera Feed --}}
            <div class="relative group w-full max-w-xs md:max-w-xl mx-auto">
                <div
                    class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-3xl blur opacity-30 group-hover:opacity-60 transition duration-500">
                </div>

                <div
                    class="relative bg-slate-800 rounded-3xl overflow-hidden shadow-2xl aspect-[4/3] border border-slate-700">

                    {{-- Loading State --}}
                    <div x-show="!isCameraOpen"
                        class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 p-4 text-center">
                        <svg class="w-12 h-12 md:w-16 md:h-16 opacity-50 mb-4 animate-pulse" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                            </path>
                        </svg>
                        <span class="text-sm md:text-base"
                            x-text="modelLoaded ? 'Klik Start untuk Memulai' : 'Memuat Model AI...'"></span>

                        <button x-show="modelLoaded" @click="startCamera()"
                            class="mt-6 px-6 py-2 md:px-8 md:py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-full font-bold shadow-lg transition transform hover:scale-105 text-sm md:text-base">
                            START CAMERA
                        </button>
                    </div>

                    <video x-ref="video" autoplay muted playsinline x-show="isCameraOpen"
                        class="w-full h-full object-cover transform scale-x-[-1]"></video>

                    {{-- Overlay Detection --}}
                    <div x-show="isCameraOpen && detectedName !== '...'"
                        class="absolute bottom-4 md:bottom-6 left-0 right-0 flex justify-center">
                        <div class="bg-black/60 backdrop-blur-md border border-white/20 px-4 py-1.5 md:px-6 md:py-2 rounded-full text-white font-semibold shadow-xl flex items-center gap-2 transition-all text-sm md:text-base"
                            :class="detectedName === 'Wajah tidak dikenal' ? 'border-red-500/50 text-red-200' : 'border-emerald-500/50 text-emerald-200'">
                            <div class="w-1.5 h-1.5 md:w-2 md:h-2 rounded-full"
                                :class="detectedName === 'Wajah tidak dikenal' ? 'bg-red-500' : 'bg-emerald-500'"></div>
                            <span x-text="detectedName"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Status & Actions --}}
            <div class="flex flex-col justify-center space-y-3 md:space-y-6 max-w-md mx-auto w-full">

                {{-- Status Card --}}
                <div
                    class="bg-slate-800/50 backdrop-blur-sm p-3 md:p-8 rounded-3xl border border-slate-700/50 text-center min-h-[110px] md:min-h-[200px] flex flex-col justify-center">
                    <template x-if="!matchedEmployeeId">
                        <div>
                            <h2 class="text-slate-500 text-base md:text-xl font-medium mb-1">Menunggu Wajah...</h2>
                            <div class="flex justify-center mt-2 space-x-2">
                                <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-slate-600 rounded-full animate-bounce"
                                    style="animation-delay: 0s"></div>
                                <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-slate-600 rounded-full animate-bounce"
                                    style="animation-delay: 0.2s"></div>
                                <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-slate-600 rounded-full animate-bounce"
                                    style="animation-delay: 0.4s"></div>
                            </div>
                        </div>
                    </template>

                    <template x-if="matchedEmployeeId">
                        <div class="animate-fade-in-up">
                            <div
                                class="mx-auto w-14 h-14 md:w-24 md:h-24 rounded-full overflow-hidden border-4 border-indigo-500/30 mb-2 md:mb-4 bg-slate-700">
                                <img :src="matchedPhoto || `https://ui-avatars.com/api/?name=${detectedName}&background=random`"
                                    class="w-full h-full object-cover">
                            </div>
                            <h2 class="text-xl md:text-3xl font-bold text-white mb-1" x-text="detectedName"></h2>
                            <div class="inline-flex items-center px-3 py-1 md:px-4 md:py-1.5 rounded-full text-[10px] md:text-sm font-bold shadow-sm"
                                :class="{
                                    'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': matchedEmployeeStatus === 'checked_in',
                                    'bg-rose-500/20 text-rose-300 border border-rose-500/30': matchedEmployeeStatus === 'checked_out',
                                    'bg-slate-700 text-slate-400 border border-slate-600': matchedEmployeeStatus === 'none'
                                }">
                                <span class="mr-1 md:mr-1.5 text-sm md:text-lg">•</span>
                                <span x-text="getStatusLabel(matchedEmployeeStatus)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Action Buttons --}}
                <div class="grid grid-cols-2 gap-3 md:gap-4">
                    {{-- Clock In Button --}}
                    <button @click="performAction('in')"
                        :disabled="!matchedEmployeeId || matchedEmployeeStatus !== 'none'"
                        class="group relative flex flex-col items-center justify-center p-3 md:p-6 rounded-2xl transition-all duration-300 transform border
                        disabled:opacity-40 disabled:cursor-not-allowed disabled:transform-none disabled:bg-slate-800 disabled:border-slate-700
                        enabled:bg-emerald-600/20 enabled:border-emerald-500/30 enabled:hover:bg-emerald-600 enabled:hover:border-emerald-400 enabled:active:scale-95">

                        <div class="mb-1 md:mb-2 p-2 md:p-3 rounded-full transition
                            disabled:bg-slate-700 disabled:text-slate-500
                            enabled:bg-emerald-500/20 enabled:group-hover:bg-white/20">
                            <svg class="w-6 h-6 md:w-8 md:h-8 transition
                                disabled:text-slate-500
                                enabled:text-emerald-400 enabled:group-hover:text-white" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1">
                                </path>
                            </svg>
                        </div>
                        <span class="text-sm md:text-xl font-bold transition
                            disabled:text-slate-500
                            enabled:text-emerald-100 enabled:group-hover:text-white">ABSEN MASUK</span>
                        <span x-show="matchedEmployeeStatus === 'checked_in'"
                            class="text-[10px] md:text-xs text-emerald-400 mt-0.5 md:mt-1">(Sudah Masuk)</span>
                    </button>

                    {{-- Clock Out Button --}}
                    <button @click="performAction('out')"
                        :disabled="!matchedEmployeeId || matchedEmployeeStatus !== 'checked_in'"
                        class="group relative flex flex-col items-center justify-center p-3 md:p-6 rounded-2xl transition-all duration-300 transform border
                        disabled:opacity-40 disabled:cursor-not-allowed disabled:transform-none disabled:bg-slate-800 disabled:border-slate-700
                        enabled:bg-rose-600/20 enabled:border-rose-500/30 enabled:hover:bg-rose-600 enabled:hover:border-rose-400 enabled:active:scale-95">

                        <div class="mb-1 md:mb-2 p-2 md:p-3 rounded-full transition
                            disabled:bg-slate-700 disabled:text-slate-500
                            enabled:bg-rose-500/20 enabled:group-hover:bg-white/20">
                            <svg class="w-6 h-6 md:w-8 md:h-8 transition
                                disabled:text-slate-500
                                enabled:text-rose-400 enabled:group-hover:text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                </path>
                            </svg>
                        </div>
                        <span class="text-sm md:text-xl font-bold transition
                            disabled:text-slate-500
                            enabled:text-rose-100 enabled:group-hover:text-white">ABSEN PULANG</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Sounds --}}
    <audio id="sound-success" src="https://cdn.freesound.org/previews/171/171671_2437358-lq.mp3"></audio>
    <audio id="sound-error" src="https://cdn.freesound.org/previews/142/142608_1840552-lq.mp3"></audio>

    <script>
        function kioskData() {
            return {
                time: '',
                message: 'Memuat sistem...',
                isCameraOpen: false,
                isLoaded: false,
                modelLoaded: false,

                video: null,
                stream: null,

                detectedName: '...',
                matchedEmployeeId: null,
                matchedEmployeeStatus: 'none',
                matchedPhoto: null,

                faceMatcher: null,
                currentDescriptor: null,
                allEmployees: @js($this->allEmployees),

                scanInterval: null,

                initKiosk() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                    this.loadFaceAPI();

                    window.addEventListener('face-verified', event => {
                        console.log("Verified:", event.detail);
                        const data = event.detail[0] || event.detail; // Handle Livewire array wrapping

                        // Update local state immediately
                        if (this.matchedEmployeeId == data.id) {
                            this.matchedEmployeeStatus = data.today_status;
                            this.detectedName = data.name;
                        }

                        // Update in-memory list so next scan is correct
                        const empIndex = this.allEmployees.findIndex(e => e.id == data.id);
                        if (empIndex !== -1) {
                            this.allEmployees[empIndex].today_status = data.today_status;
                        }
                    });

                    window.addEventListener('play-sound', event => {
                        const type = event.detail.type || 'success'; // success, error
                        const audio = document.getElementById('sound-' + type);
                        if (audio) { audio.currentTime = 0; audio.play().catch(e => console.log(e)); }
                    });
                },

                updateTime() {
                    const now = new Date();
                    this.time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':');
                },

                loadFaceAPI() {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js';
                    script.onload = () => this.loadModels();
                    document.head.appendChild(script);
                },

                async loadModels() {
                    const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

                    this.message = 'Inisialisasi TensorFlow (Force CPU)...';
                    await wait(100);

                    try {
                        // Force CPU backend immediately to avoid WebGL hang on some mobile GPUs
                        // This is safer/stable even if slightly slower
                        await faceapi.tf.setBackend('cpu');
                        await faceapi.tf.ready();

                        console.log('TF Backend Active:', faceapi.tf.getBackend());
                        this.message = 'Backend: ' + faceapi.tf.getBackend();
                        await wait(500);

                        const modelPath = '/models';

                        // Helper to load with timeout
                        const loadWithTimeout = async (task, name) => {
                            const timeout = new Promise((_, reject) =>
                                setTimeout(() => reject(new Error(`Timeout memuat ${name}`)), 10000)
                            );
                            return Promise.race([task, timeout]);
                        };

                        this.message = 'Memuat TinyFace (Timeout 10s)...';
                        await wait(100);
                        await loadWithTimeout(faceapi.nets.tinyFaceDetector.loadFromUri(modelPath), 'TinyFace');

                        this.message = 'TinyFace OK. Memuat Landmarks...';
                        await wait(100);
                        await loadWithTimeout(faceapi.nets.faceLandmark68Net.loadFromUri(modelPath), 'Landmarks');

                        this.message = 'Landmarks OK. Memuat Recog...';
                        await wait(100);
                        await loadWithTimeout(faceapi.nets.faceRecognitionNet.loadFromUri(modelPath), 'Recognition');

                        this.message = 'Siap. Memproses...';
                        setTimeout(() => this.initMatcher(), 200);

                    } catch (e) {
                        console.error("Model Error:", e);
                        this.message = 'Gagal: ' + (e.message || e);
                        alert('Error: ' + (e.message || e) + '\nCoba refresh atau gunakan browser lain.');
                    }
                },

                initMatcher() {
                    try {
                        if (this.allEmployees && this.allEmployees.length > 0) {
                            const labeledDescriptors = this.allEmployees
                                .filter(emp => emp.descriptors && emp.descriptors.length > 0)
                                .map(emp => {
                                    const descriptors = emp.descriptors.map(d => new Float32Array(d));
                                    return new faceapi.LabeledFaceDescriptors(emp.id.toString(), descriptors);
                                });

                            if (labeledDescriptors.length > 0) {
                                // Relaxed threshold to 0.50 (was 0.45) for faster/easier matching
                                this.faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.50);
                            }
                        }

                        this.modelLoaded = true;
                        this.message = 'Sistem Siap. Klik START.';
                    } catch (err) {
                        console.error(err);
                        this.message = 'Gagal memproses data karyawan.';
                    }
                },



                async startCamera() {
                    if (!this.modelLoaded) return;
                    this.message = 'Menghubungkan kamera...';

                    try {
                        const constraints = {
                            video: {
                                facingMode: 'user',
                                width: { ideal: 640 },
                                height: { ideal: 480 }
                            }
                        };
                        this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                        this.video = this.$refs.video;
                        this.video.srcObject = this.stream;
                        this.isCameraOpen = true;
                        this.message = 'Mendeteksi wajah...';

                        this.detectLoop();
                    } catch (err) {
                        this.message = 'Gagal akses kamera: ' + err.message;
                    }
                },

                // Debounce counter for unknown faces
                unknownFrames: 0,
                // Debounce counter for no face detected
                noFaceFrames: 0,

                async detectLoop() {
                    if (!this.isCameraOpen || !this.video) return;

                    if (!this.video.paused && !this.video.ended && this.video.readyState === 4) {
                        // OPTIMIZATION: Reduce inputSize to 160 for faster CPU inference
                        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 });
                        const detection = await faceapi.detectSingleFace(this.video, options).withFaceLandmarks().withFaceDescriptor();

                        if (detection) {
                            this.noFaceFrames = 0; // Reset no-face counter
                            this.currentDescriptor = detection.descriptor;

                            if (this.faceMatcher) {
                                const bestMatch = this.faceMatcher.findBestMatch(detection.descriptor);
                                if (bestMatch.label !== 'unknown') {
                                    // Match found
                                    this.unknownFrames = 0; // Reset unknown counter
                                    const matchedId = bestMatch.label;
                                    if (this.matchedEmployeeId != matchedId) {
                                        this.matchedEmployeeId = matchedId;
                                        const emp = this.allEmployees.find(e => e.id == matchedId);
                                        if (emp) {
                                            this.detectedName = emp.name;
                                            this.matchedEmployeeStatus = emp.today_status;
                                            this.matchedPhoto = emp.photo;
                                        }
                                    }
                                } else {
                                    // Unknown
                                    this.unknownFrames++;
                                    // Only switch to 'Unknown' if we have 2 consecutive unknown frames (Faster response)
                                    if (this.unknownFrames > 2) {
                                        this.detectedName = 'Wajah tidak dikenal';
                                        this.matchedEmployeeId = null;
                                        this.matchedEmployeeStatus = 'none';
                                    }
                                }
                            } else {
                                this.detectedName = 'Belum ada data wajah';
                            }
                        } else {
                            // No face detected
                            this.noFaceFrames++;
                            if (this.noFaceFrames > 2) { // 2 frames debounce for instant clear
                                this.detectedName = '...';
                                this.matchedEmployeeId = null;
                                this.matchedEmployeeStatus = 'none';
                            }
                        }
                    }

                    if (this.isCameraOpen) {
                        // Scan logic: Reduced throttle to 30ms for smoother experience
                        setTimeout(() => {
                            requestAnimationFrame(() => this.detectLoop());
                        }, 30);
                    }
                },

                async performAction(type) { // in, out
                    if (!this.matchedEmployeeId || !this.currentDescriptor) return;

                    // Capture snapshot
                    const canvas = document.createElement('canvas');
                    canvas.width = this.video.videoWidth;
                    canvas.height = this.video.videoHeight;
                    // Mirror flip for consistency with view? Backend doesn't care but nice for storage.
                    const ctx = canvas.getContext('2d');
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(this.video, 0, 0);
                    const snapshot = canvas.toDataURL('image/jpeg', 0.8);

                    const descriptorArray = Array.from(this.currentDescriptor);

                    this.message = 'Memproses...';

                    // Send to Livewire
                    await this.$wire.handleFaceDetected({
                        descriptor: descriptorArray,
                        snapshot: snapshot,
                        mode: type
                    });

                    this.message = 'Siap.';
                },

                getStatusLabel(status) {
                    if (status === 'checked_in') return 'Sedang Bekerja';
                    if (status === 'checked_out') return 'Sudah Pulang';
                    return 'Belum Absen';
                }
            }
        }
    </script>
</div>