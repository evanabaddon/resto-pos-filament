<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            descriptorsState: $wire.$entangle('data.face_descriptor'), // Binding to the sibling field
            video: null,
            stream: null,
            captures: [],
            isCameraOpen: false,
            modelLoaded: false,
            status: 'Menunggu kamera...',
            
            async init() {
                // Initialize captures from existing state if any (for Edit mode)
                if (this.state && Array.isArray(this.state)) {
                    this.captures = this.state.map(path => {
                        return path.startsWith('data:image') ? path : '/storage/' + path;
                    });
                }

                // Load Face API
                if (typeof faceapi === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js';
                    script.onload = () => this.loadModels();
                    document.head.appendChild(script);
                } else {
                    this.loadModels();
                }
            },

            async loadModels() {
                this.status = 'Memuat model AI...';
                try {
                    await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
                    await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
                    await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
                    this.modelLoaded = true;
                    this.status = 'Model siap. Klik Buka Kamera.';
                } catch (e) {
                    this.status = 'Gagal memuat model: ' + e;
                }
            },

            async startCamera() {
                if (!this.modelLoaded) return;
                
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({ video: {} });
                    this.video = this.$refs.video;
                    this.video.srcObject = this.stream;
                    this.isCameraOpen = true;
                    this.status = 'Kamera aktif. Silakan ambil foto.';
                    this.detectFaces();
                } catch (err) {
                    this.status = 'Gagal akses kamera: ' + err;
                }
            },

            stopCamera() {
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }
                this.isCameraOpen = false;
                this.status = 'Kamera dimatikan.';
            },

            async detectFaces() {
                if (!this.isCameraOpen || !this.video) return;

                // Loop for visual feedback (box) can be added here if we had a canvas overlay
                // For now, relies on the Capture action to do the actual 'Detection check'
                
                // requestAnimationFrame(() => this.detectFaces());
            },

            async capture() {
                if (this.captures.length >= 3) {
                    alert('Maksimal 3 foto.');
                    return;
                }

                // Detect face before capturing to ensure quality
                const detections = await faceapi.detectAllFaces(this.video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                if (detections.length === 0) {
                    alert('Wajah tidak terdeteksi! Pastikan wajah terlihat jelas.');
                    return;
                }
                
                if (detections.length > 1) {
                    alert('Terdeteksi lebih dari 1 wajah. Harap foto sendiri.');
                    return;
                }

                const descriptor = Array.from(detections[0].descriptor);

                // Draw to canvas
                const canvas = document.createElement('canvas');
                canvas.width = this.video.videoWidth;
                canvas.height = this.video.videoHeight;
                canvas.getContext('2d').drawImage(this.video, 0, 0);
                const imageData = canvas.toDataURL('image/png');

                this.captures.push(imageData);
                
                // Update State (Files)
                // We mix new base64 with old paths. The dehydration logic will sort it out.
                this.state = this.captures; 

                // Update Descriptors
                // We need to fetch existing descriptors? 
                // Creating a new array implies overwriting. 
                // For simplicity: If we are adding photos, we append descriptors.
                // NOTE: Managing descriptors hidden field sync is tricky if we mix edits.
                // simplified: We assume we are REPLACING or ADDING to a fresh session mostly.
                // Let's rely on the form save to handle descriptors? 
                // NO, we want to capture the descriptor NOW because we have the raw pixel data and the model loaded.
                // Computing it later on backend requires backend models (which we don't have, we only have JS models).
                
                let currentDescriptors = this.descriptorsState || [];
                if (!Array.isArray(currentDescriptors)) currentDescriptors = [];
                currentDescriptors.push(descriptor);
                this.descriptorsState = currentDescriptors;
                
                this.status = 'Foto berhasil diambil (' + this.captures.length + '/3)';
            },

            removeCapture(index) {
                this.captures.splice(index, 1);
                this.state = this.captures;
                
                // Also remove corresponding descriptor
                let currentDescriptors = this.descriptorsState || [];
                if (Array.isArray(currentDescriptors) && currentDescriptors.length > index) {
                    currentDescriptors.splice(index, 1);
                    this.descriptorsState = currentDescriptors;
                }
            }
        }">
        <div class="space-y-4">
            <!-- Camera Section -->
            <div class="relative bg-black rounded-lg overflow-hidden aspect-video flex items-center justify-center">
                <template x-if="!isCameraOpen">
                    <div class="text-center text-gray-400">
                        <p class="mb-2" x-text="status"></p>
                        <x-filament::button x-on:click="startCamera" x-bind:disabled="!modelLoaded" size="sm">
                            Buka Kamera
                        </x-filament::button>
                    </div>
                </template>
                <video x-ref="video" autoplay playsinline class="w-full h-full object-cover"
                    x-show="isCameraOpen"></video>

                <div class="absolute bottom-4 left-0 right-0 flex justify-center space-x-4" x-show="isCameraOpen">
                    <x-filament::button color="danger" x-on:click="stopCamera" size="sm">
                        Tutup
                    </x-filament::button>
                    <x-filament::button color="success" x-on:click="capture" size="sm">
                        Ambil Foto
                    </x-filament::button>
                </div>
            </div>

            <!-- Gallery -->
            <div class="grid grid-cols-3 gap-4">
                <template x-for="(image, index) in captures" :key="index">
                    <div class="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border">
                        <img :src="image" class="w-full h-full object-cover" />
                        <button type="button" x-on:click="removeCapture(index)"
                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition">
                            <x-heroicon-o-x-mark class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </div>

            <div class="text-xs text-gray-500">
                <p>Status: <span x-text="status"></span></p>
                <p>Model Loaded: <span x-text="modelLoaded ? 'Ya' : 'Tidak'"></span></p>
            </div>
        </div>
    </div>
</x-dynamic-component>