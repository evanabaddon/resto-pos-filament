<x-filament-panels::page>
    <div x-data="{ 
            // ... (keep existing data structure)
            video: null,
            isLoaded: false,
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
                script.onload = () => this.startCamera();
                document.head.appendChild(script);
            },

            async startCamera() {
                this.message = 'Memuat model AI...';
                await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
                await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
                await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
                await faceapi.nets.ssdMobilenetv1.loadFromUri('/models');
                
                this.message = 'Menyiapkan data wajah...';
                if (this.allEmployees && this.allEmployees.length > 0) {
                    const labeledDescriptors = this.allEmployees.map(emp => {
                        const descriptors = emp.descriptors.map(d => new Float32Array(d));
                        return new faceapi.LabeledFaceDescriptors(emp.id + '|' + emp.name + '|' + emp.today_status, descriptors);
                    });
                    this.faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
                }
                
                this.message = 'Membuka kamera...';
                this.video = this.$refs.video;
                
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
                    this.video.srcObject = stream;
                    this.isLoaded = true;
                    this.message = 'Siap. Silakan berdiri di depan kamera.';
                    this.detectFace();
                } catch (err) {
                    this.message = 'Gagal akses kamera: ' + err;
                }
            },

            async detectFace() {
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
                         this.matchedEmployeeId = null;
                         this.matchedEmployeeStatus = 'none';
                    }
                }
                setTimeout(() => this.detectFace(), 500);
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
                }
            }
        }" class="flex flex-col items-center justify-start pt-10 p-4 min-h-screen">
        <!-- Header Section -->
        <div class="w-full max-w-5xl mb-6 text-center">
            <h1
                class="text-3xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-indigo-600 mb-2 tracking-tight">
                Mesin Absensi
            </h1>
            <p class="text-sm md:text-lg text-gray-500 dark:text-gray-400 font-medium" x-text="message"></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-6xl">
            <!-- Video Section -->
            <div class="relative group order-first md:order-none">
                <div
                    class="absolute -inset-0.5 bg-gradient-to-r from-primary-500 to-indigo-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition duration-500">
                </div>

                <div
                    class="relative bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-xl aspect-video md:aspect-[4/3] border border-gray-100 dark:border-gray-700">
                    <video x-ref="video" autoplay muted playsinline
                        class="w-full h-full object-cover transform scale-x-[-1]"></video>

                    <!-- Overlay Name Badge -->
                    <div class="absolute bottom-4 left-0 right-0 flex justify-center">
                        <div class="bg-black/40 backdrop-blur-md border border-white/10 px-6 py-2 rounded-full text-white font-semibold shadow-lg transition-all duration-300"
                            :class="detectedName !== '...' && detectedName !== 'Wajah tidak dikenal' ? 'scale-100 opacity-100' : 'scale-95 opacity-0'">
                            <span class="text-lg md:text-xl tracking-wide" x-text="detectedName"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Controls Section -->
            <div class="flex flex-col justify-center space-y-6">

                <!-- Status Card -->
                <div
                    class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 text-center transition-all hover:shadow-xl">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Status Karyawan</p>

                    <template x-if="!matchedEmployeeId">
                        <h2 class="text-2xl font-bold text-gray-400">Menunggu Wajah...</h2>
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