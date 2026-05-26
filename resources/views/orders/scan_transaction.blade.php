@extends('layouts.admin')

@section('title', 'Scan Validasi Transaksi - Sewana')
@section('meta_description', 'Scan QR Code validasi transaksi Sewana melalui kamera atau unggah gambar QR.')

@section('content')
    @php
        $resolveTemplate = route('pegawai.transactions.resolve', ['token' => '__TOKEN__']);
    @endphp

    <div class="admin-page admin-page--wide">
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Validasi Transaksi</span>
                <h1 class="admin-page-title">Scan QR Code</h1>
                <p class="admin-page-subtitle">Pindai QR validasi transaksi Sewana melalui kamera atau unggah gambar QR.</p>
            </div>

            <a href="{{ route(auth()->user()->hasRole('pemilik') ? 'pemilik.orders.index' : 'pegawai.orders.index') }}"
                class="btn btn-outline-dark rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <div id="scanner-alert" class="alert d-none rounded-4 shadow-sm" role="alert"></div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="admin-card h-100">
                    <div class="admin-card-header">
                        <span class="admin-page-eyebrow">Kamera</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">Scan Lewat Kamera</h5>
                    </div>

                    <div class="admin-card-body">
                        <div class="bg-dark rounded-4 overflow-hidden position-relative mb-3">
                            <video id="qr-video" class="w-100 d-block" style="min-height: 320px; object-fit: cover;"
                                autoplay muted playsinline></video>
                            <div id="camera-placeholder"
                                class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white bg-dark">
                                <i class="bi bi-camera fs-1 mb-2"></i>
                                <span class="fw-semibold">Kamera belum aktif</span>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="button" id="start-camera" class="btn btn-dark rounded-pill px-4">
                                <i class="bi bi-camera-video me-1"></i> Mulai Scan
                            </button>
                            <button type="button" id="stop-camera" class="btn btn-outline-secondary rounded-pill px-4"
                                disabled>
                                <i class="bi bi-stop-circle me-1"></i> Stop Kamera
                            </button>
                        </div>

                        <canvas id="qr-canvas" class="d-none"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="admin-card h-100">
                    <div class="admin-card-header">
                        <span class="admin-page-eyebrow">Upload</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">Validasi dari Gambar</h5>
                    </div>

                    <div class="admin-card-body">
                        <label for="qr-upload" class="form-label admin-form-label">Unggah gambar QR Code</label>
                        <input type="file" id="qr-upload" class="form-control" accept="image/*">
                        <small class="text-muted d-block mt-2">
                            Sistem hanya menerima QR yang berisi URL validasi transaksi Sewana.
                        </small>
                        <button type="button" id="decode-upload" class="btn btn-dark rounded-pill px-4 mt-3" disabled>
                            <i class="bi bi-check2-circle me-1"></i> Validasi QR dari Gambar
                        </button>

                        <div class="border rounded-4 p-3 mt-4 bg-light">
                            <div class="d-flex align-items-start gap-3">
                                <div class="bg-white rounded-circle text-dark d-flex align-items-center justify-content-center"
                                    style="width: 42px; height: 42px;">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">Hasil validasi aman</div>
                                    <div class="text-muted small">
                                        Setelah QR terbaca, sistem hanya mengambil token lalu membuka detail pesanan
                                        melalui endpoint pegawai tanpa menampilkan data sensitif di scanner.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="upload-preview" class="mt-4 d-none">
                            <div class="admin-mini-label mb-2">Preview gambar</div>
                            <img id="upload-preview-image" class="img-fluid rounded-4 border bg-white"
                                alt="Preview gambar QR yang diunggah">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <script>
        (() => {
            const resolveTemplate = @json($resolveTemplate);
            const resolveUrl = (token) => resolveTemplate.replace('__TOKEN__', encodeURIComponent(token));
            const alertBox = document.getElementById('scanner-alert');
            const video = document.getElementById('qr-video');
            const canvas = document.getElementById('qr-canvas');
            const placeholder = document.getElementById('camera-placeholder');
            const startButton = document.getElementById('start-camera');
            const stopButton = document.getElementById('stop-camera');
            const uploadInput = document.getElementById('qr-upload');
            const decodeUploadButton = document.getElementById('decode-upload');
            const uploadPreview = document.getElementById('upload-preview');
            const uploadPreviewImage = document.getElementById('upload-preview-image');

            let detector = null;
            let stream = null;
            let scanLoopId = null;
            let redirected = false;
            let previewObjectUrl = null;
            let selectedUploadFile = null;

            function showAlert(type, message) {
                alertBox.className = `alert alert-${type} rounded-4 shadow-sm`;
                alertBox.textContent = message;
            }

            function hideAlert() {
                alertBox.className = 'alert d-none rounded-4 shadow-sm';
                alertBox.textContent = '';
            }

            async function createDetector() {
                if (!('BarcodeDetector' in window)) {
                    return null;
                }

                try {
                    const formats = await window.BarcodeDetector.getSupportedFormats();
                    if (!formats.includes('qr_code')) {
                        return null;
                    }

                    return new window.BarcodeDetector({
                        formats: ['qr_code']
                    });
                } catch (error) {
                    return null;
                }
            }

            function extractValidationToken(value) {
                const rawValue = String(value || '').trim();
                if (!rawValue) {
                    return null;
                }

                const directMatch = rawValue.match(/\/verify-transaction\/([A-Za-z0-9]{32,128})(?:[/?#\s]|$)/);
                if (directMatch) {
                    return directMatch[1];
                }

                let parsedUrl = null;
                try {
                    parsedUrl = new URL(rawValue, window.location.origin);
                } catch (error) {
                    return null;
                }

                const pathParts = parsedUrl.pathname.replace(/\/+$/, '').split('/').filter(Boolean);
                const verifyIndex = pathParts.indexOf('verify-transaction');

                if (verifyIndex === -1 || verifyIndex + 1 !== pathParts.length - 1) {
                    return null;
                }

                const token = pathParts[verifyIndex + 1];
                return /^[A-Za-z0-9]{32,128}$/.test(token) ? token : null;
            }

            function handleQrValue(value) {
                const token = extractValidationToken(value);

                if (!token) {
                    showAlert('danger', 'QR Code tidak valid. Gunakan QR Code validasi transaksi Sewana.');
                    return false;
                }

                redirected = true;
                stopCamera();
                showAlert('success', 'QR Code valid. Membuka detail pesanan...');
                window.location.href = resolveUrl(token);
                return true;
            }

            async function detectWithBarcodeDetector(source) {
                if (!detector || redirected) {
                    return false;
                }

                try {
                    const codes = await detector.detect(source);
                    if (!codes.length) {
                        return false;
                    }

                    return handleQrValue(codes[0].rawValue);
                } catch (error) {
                    return false;
                }
            }

            function detectWithJsQrFromCanvas(sourceCanvas) {
                if (!window.jsQR || redirected || !sourceCanvas.width || !sourceCanvas.height) {
                    return false;
                }

                const context = sourceCanvas.getContext('2d', {
                    willReadFrequently: true
                });
                const imageData = context.getImageData(0, 0, sourceCanvas.width, sourceCanvas.height);
                const code = window.jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: 'attemptBoth'
                });

                return code ? handleQrValue(code.data) : false;
            }

            async function decodeCanvas(sourceCanvas) {
                if (redirected) {
                    return false;
                }

                detector = detector || await createDetector();

                if (detector && await detectWithBarcodeDetector(sourceCanvas)) {
                    return true;
                }

                return detectWithJsQrFromCanvas(sourceCanvas);
            }

            async function scanVideoFrameLoop() {
                if (!stream || redirected) {
                    return;
                }

                if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    await decodeCanvas(canvas);
                }

                if (!redirected && stream) {
                    scanLoopId = window.requestAnimationFrame(scanVideoFrameLoop);
                }
            }

            async function startCamera() {
                hideAlert();

                if (!navigator.mediaDevices?.getUserMedia) {
                    showAlert('warning', 'Browser ini belum mendukung akses kamera. Gunakan upload gambar QR.');
                    return;
                }

                if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(window.location.hostname)) {
                    showAlert('warning', 'Kamera browser membutuhkan HTTPS atau localhost. Gunakan upload gambar QR jika kamera tidak tersedia.');
                    return;
                }

                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: {
                                ideal: 'environment'
                            }
                        },
                        audio: false
                    });

                    video.srcObject = stream;
                    placeholder.classList.add('d-none');
                    startButton.disabled = true;
                    stopButton.disabled = false;
                    scanLoopId = window.requestAnimationFrame(scanVideoFrameLoop);
                    showAlert('info', 'Kamera aktif. Arahkan ke QR Code validasi transaksi.');
                } catch (error) {
                    showAlert('danger', 'Kamera tidak dapat diakses. Periksa izin kamera browser atau gunakan upload gambar QR.');
                }
            }

            function stopCamera() {
                if (scanLoopId) {
                    window.cancelAnimationFrame(scanLoopId);
                    scanLoopId = null;
                }

                if (stream) {
                    stream.getTracks().forEach((track) => track.stop());
                    stream = null;
                }

                video.srcObject = null;
                placeholder.classList.remove('d-none');
                startButton.disabled = false;
                stopButton.disabled = true;
            }

            function drawImageToCanvas(image) {
                const maxSize = 1400;
                const ratio = Math.min(1, maxSize / Math.max(image.naturalWidth || image.width, image.naturalHeight || image.height));
                canvas.width = Math.max(1, Math.round((image.naturalWidth || image.width) * ratio));
                canvas.height = Math.max(1, Math.round((image.naturalHeight || image.height) * ratio));
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
            }

            async function processUploadFile(file) {
                hideAlert();

                if (!file) {
                    showAlert('warning', 'Pilih gambar QR Code terlebih dahulu.');
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    showAlert('danger', 'File harus berupa gambar QR Code.');
                    return;
                }

                showAlert('info', 'Memproses QR dari gambar...');

                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                }

                previewObjectUrl = URL.createObjectURL(file);
                uploadPreviewImage.src = previewObjectUrl;
                uploadPreview.classList.remove('d-none');

                try {
                    const image = new Image();
                    image.onload = async () => {
                        drawImageToCanvas(image);
                        const found = await decodeCanvas(canvas);

                        if (!found && !redirected) {
                            showAlert('danger', 'QR Code tidak terbaca atau bukan QR validasi transaksi Sewana. Pastikan gambar jelas dan QR tidak terpotong.');
                        }
                    };
                    image.onerror = () => {
                        showAlert('danger', 'Gambar tidak dapat diproses. Coba gunakan gambar QR yang lebih jelas.');
                    };
                    image.src = previewObjectUrl;
                } catch (error) {
                    showAlert('danger', 'Gambar tidak dapat diproses. Coba gunakan gambar QR yang lebih jelas.');
                }
            }

            async function handleUpload(event) {
                selectedUploadFile = event.target.files?.[0] || null;
                decodeUploadButton.disabled = !selectedUploadFile;

                if (selectedUploadFile) {
                    await processUploadFile(selectedUploadFile);
                }
            }

            startButton.addEventListener('click', startCamera);
            stopButton.addEventListener('click', stopCamera);
            uploadInput.addEventListener('change', handleUpload);
            decodeUploadButton.addEventListener('click', () => processUploadFile(selectedUploadFile));
            window.addEventListener('beforeunload', () => {
                stopCamera();
                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                }
            });
        })();
    </script>
@endsection
