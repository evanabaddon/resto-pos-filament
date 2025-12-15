<x-filament-panels::page>
    <div x-data="{ 
            video: null,
            stream: null,
            isLoaded: false,
            isCameraOpen: false,
            modelLoaded: false,
            message: 'Memuat sistem...',
            
            detectedName: 'Mendeteksi...',
            matchedEmployeeId: null,
            matchedEmployeeStatus: 'none', 
            faceMatcher: null,
            allEmployees: @js($this->allEmployees),
            currentDescriptor: null,
            
            init() {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js';
                // script.onload = () => this.loadModels(); // DEFER LOADING
                document.head.appendChild(script);
                this.message = 'Sistem Siap. Klik tombol untuk memulai.';
            },

            async loadModels() {
                this.message = 'Sedang memuat model AI (ini mungkin memakan waktu)...';
                try {
                    await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
                    await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
                    await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
                    // await faceapi.nets.ssdMobilenetv1.loadFromUri('/models'); // Unused & Heavy
                    
                    this.modelLoaded = true;
                } catch (e) {
                    this.message = 'Gagal memuat model AI: ' + e;
                    throw e;
                }
            },

            async startCamera() {
                // Determine if we need to load models
                if (!this.modelLoaded) {
                    try {
                        await this.loadModels();
                    } catch (e) {
                        alert('Gagal memuat model: ' + e);
                        return;
                    }
                }

                this.message = 'Menyiapkan data wajah...';
                
                // Prepare Matcher
                if (this.allEmployees && this.allEmployees.length > 0 && !this.faceMatcher) {
                    const labeledDescriptors = this.allEmployees.map(emp => {
                        const descriptors = emp.descriptors.map(d => new Float32Array(d));
                        return new faceapi.LabeledFaceDescriptors(emp.id + '|' + emp.name + '|' + emp.today_status, descriptors);
                    });
                    this.faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
                }
                
                this.message = 'Membuka kamera...';
                
                try {
                    // Mobile Optimization: Prefer Front Camera & Lower Resolution
                    // Lower resolution (640x480) makes FaceAPI much faster on mobile
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
                    this.isLoaded = true;
                    this.message = 'Kamera Aktif. Silakan berdiri di depan kamera.';
                    
                    this.detectFace();
                } catch (err) {
                    console.error(err);
                    this.message = 'Gagal akses kamera: ' + err.name + ' - ' + err.message;
                    alert('Gagal membuka kamera: ' + err.message + '\nPastikan izin kamera diberikan dan akses via HTTPS.');
                }
            },

            stopCamera() {
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }
                this.isCameraOpen = false;
                this.message = 'Kamera dimatikan.';
                this.detectedName = '...';
                this.matchedEmployeeId = null;
                this.matchedEmployeeStatus = 'none';
            },

            async detectFace() {
                if (!this.isCameraOpen) return;
                
                if (!this.video.paused && !this.video.ended) {
                    const detections = await faceapi.detectAllFaces(this.video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptors();
                    
                    if (detections.length > 0) {
                         const descriptor = detections[0].descriptor;
                         this.currentDescriptor = descriptor;
                         
                         if (this.faceMatcher) {
                             const bestMatch = this.faceMatcher.findBestMatch(descriptor);
                             if (bestMatch.label !== 'unknown') {
                                 const parts = bestMatch.label.split('|');
                                 this.matchedEmployeeId = parts[0];
                                 this.detectedName = parts[1];
                                 
                                 const emp = this.allEmployees.find(e => e.id == this.matchedEmployeeId);
                                 this.matchedEmployeeStatus = emp ? emp.today_status : 'none';
                             } else {
                                 this.detectedName = 'Wajah tidak dikenal';
                                 this.matchedEmployeeId = null;
                                 this.matchedEmployeeStatus = 'none';
                             }
                         }
                    } else {
                         this.detectedName = '...';
                         // Keep last detected for a bit or clear? Clear is safer.
                         // this.matchedEmployeeId = null;
                         // this.matchedEmployeeStatus = 'none';
                    }
                }
                if (this.isCameraOpen) {
                    setTimeout(() => this.detectFace(), 500);
                }
            },

            async performClockIn() {
                 if (!this.matchedEmployeeId || !this.currentDescriptor) return;
                 await this.sendAttendance('clockIn');
            },
            
            async performClockOut() {
                 if (!this.matchedEmployeeId || !this.currentDescriptor) return;
                 await this.sendAttendance('clockOut');
            },

            async sendAttendance(method) {
                const canvas = document.createElement('canvas');
                canvas.width = this.video.videoWidth;
                canvas.height = this.video.videoHeight;
                canvas.getContext('2d').drawImage(this.video, 0, 0);
                const snapshot = canvas.toDataURL('image/png');
                const descriptorArray = Array.from(this.currentDescriptor);

                await $wire[method](descriptorArray, snapshot);
                
                const empIndex = this.allEmployees.findIndex(e => e.id == this.matchedEmployeeId);
                if (empIndex !== -1) {
                    if (method === 'clockIn') this.allEmployees[empIndex].today_status = 'checked_in';
                    if (method === 'clockOut') this.allEmployees[empIndex].today_status = 'checked_out';
                    
                    // Update matcher label if status changed to reflect visually immediately? 
                    // Complex to re-init matcher. UI status update is handled by matchedEmployeeStatus reactively.
                }
            }
        }" class="flex flex-col items-center justify-center p-4">
        <!-- Header Section -->
        <div class="w-full max-w-5xl mb-6 text-center">
            <h1
                class="text-3xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-2 tracking-tight">
                Mesin Absensi
            </h1>
            <p class="text-sm md:text-lg text-gray-500 dark:text-gray-400 font-medium" x-text="message"></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-6xl">
            <!-- Video Section -->
            <div class="relative group order-first md:order-none">
                <div
                    class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition duration-500">
                </div>

                <div
                    class="relative bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-xl aspect-video md:aspect-[4/3] border border-gray-100 dark:border-gray-700 flex items-center justify-center">

                    <!-- Pre-Camera State -->
                    <template x-if="!isCameraOpen">
                        <div class="text-center p-6">
                            <div class="mb-4 text-gray-400">
                                <x-heroicon-o-video-camera class="w-16 h-16 mx-auto mb-2 opacity-50" />
                                <p class="text-sm"
                                    x-text="modelLoaded ? 'Sistem Siap Digunakan' : 'Memuat Resource...'"></p>
                            </div>

                            <button @click="startCamera"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-wait transition-all">
                                <x-heroicon-m-play class="w-5 h-5 mr-2" />
                                <span x-text="modelLoaded ? 'Buka Kamera Absensi' : 'Memuat & Buka Kamera'"></span>
                            </button>
                        </div>
                    </template>

                    <!-- Camera Active State -->
                    <video x-ref="video" autoplay muted playsinline x-show="isCameraOpen"
                        class="w-full h-full object-cover transform scale-x-[-1]"></video>

                    <!-- Overlay Name Badge (Only show when camera is active) -->
                    <div class="absolute bottom-4 left-0 right-0 flex justify-center" x-show="isCameraOpen">
                        <div class="bg-black/40 backdrop-blur-md border border-white/10 px-6 py-2 rounded-full text-white font-semibold shadow-lg transition-all duration-300"
                            :class="detectedName !== '...' && detectedName !== 'Wajah tidak dikenal' ? 'scale-100 opacity-100' : 'scale-95 opacity-0'">
                            <span class="text-lg md:text-xl tracking-wide" x-text="detectedName"></span>
                        </div>
                    </div>
                </div>

                <!-- Close Button (Outside the video box for easier access) -->
                <div class="relative z-10 mt-4 text-center" x-show="isCameraOpen">
                    <button @click="stopCamera"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <x-heroicon-m-stop class="w-4 h-4 mr-2" />
                        Tutup Kamera
                    </button>
                </div>
            </div>

            <!-- Controls Section -->
            <div class="flex flex-col justify-center space-y-6">

                <!-- Status Card -->
                <div
                    class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 text-center transition-all hover:shadow-xl">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Status Karyawan</p>

                    <template x-if="!matchedEmployeeId">
                        <h2 class="text-2xl font-bold text-gray-400"
                            x-text="isCameraOpen ? 'Menunggu Wajah...' : 'Kamera Mati'"></h2>
                    </template>

                    <template x-if="matchedEmployeeId">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2" x-text="detectedName">
                            </h2>
                            <div class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold shadow-sm"
                                :class="{
                                    'bg-green-100 text-green-700 border border-green-200': matchedEmployeeStatus === 'checked_in',
                                    'bg-red-100 text-red-700 border border-red-200': matchedEmployeeStatus === 'checked_out',
                                    'bg-gray-100 text-gray-600 border border-gray-200': matchedEmployeeStatus === 'none'
                                }">
                                <span class="mr-1.5 text-lg">•</span>
                                <span
                                    x-text="matchedEmployeeStatus === 'none' ? 'Belum Absen' : (matchedEmployeeStatus === 'checked_in' ? 'Sedang Bekerja' : 'Sudah Pulang')"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Buttons Grid -->
                <div class="grid grid-cols-2 md:grid-cols-1 gap-4">
                    <button @click="performClockIn" :disabled="!matchedEmployeeId || matchedEmployeeStatus !== 'none'"
                        class="group relative w-full py-4 md:py-5 rounded-2xl text-white font-bold text-lg md:text-xl shadow-lg transition-all duration-200 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500">
                        <!-- Button Content -->
                        <div class="relative z-10 flex flex-col md:flex-row items-center justify-center gap-2">
                            <x-heroicon-m-arrow-right-end-on-rectangle class="w-8 h-8" />
                            <span>CLOCK IN</span>
                        </div>
                    </button>

                    <button @click="performClockOut"
                        :disabled="!matchedEmployeeId || matchedEmployeeStatus !== 'checked_in'"
                        class="group relative w-full py-4 md:py-5 rounded-2xl text-white font-bold text-lg md:text-xl shadow-lg transition-all duration-200 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 overflow-hidden bg-gradient-to-br from-rose-500 to-red-600 hover:from-rose-400 hover:to-red-500">
                        <div class="relative z-10 flex flex-col md:flex-row items-center justify-center gap-2">
                            <x-heroicon-m-arrow-left-start-on-rectangle class="w-8 h-8" />
                            <span>CLOCK OUT</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>