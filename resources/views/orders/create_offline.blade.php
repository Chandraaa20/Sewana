@extends('layouts.admin')

@section('content')
    @php
        $today = now()->toDateString();
        $oldStartDate = old('start_date');
        $minimumEndDate =
            $oldStartDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $oldStartDate) && $oldStartDate >= $today
                ? $oldStartDate
                : $today;
    @endphp

    <div class="admin-page">
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Operasional</span>
                <h1 class="admin-page-title">Tambah Pesanan Offline</h1>
                <p class="admin-page-subtitle">Catat transaksi langsung, pilih produk, dan cek estimasi harga sebelum
                    disimpan.</p>
            </div>
            <a href="{{ route('pegawai.orders.index') }}" class="btn btn-outline-dark rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- Error --}}
        @if ($errors->any())
            <div class="alert alert-danger rounded-4 shadow-sm border-0">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pegawai.orders.offline.store') }}" method="POST" enctype="multipart/form-data"
            class="admin-card">
            @csrf

            <div class="admin-card-header">
                <span class="admin-page-eyebrow">Data Pesanan</span>
                <h5 class="fw-bold text-dark mb-0 mt-1">Informasi Penyewa dan Produk</h5>
            </div>

            <div class="admin-card-body">
                <div class="row g-3">
                    {{-- Customer name --}}
                    <div class="col-md-6">
                        <label class="form-label admin-form-label">Nama Pelanggan</label>
                        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}"
                            required>
                    </div>

                    {{-- Identity photo --}}
                    <div class="col-md-6">
                        <label class="form-label admin-form-label">Foto Identitas (KTP/SIM)</label>
                        <input type="file" name="identity_photo" class="form-control" accept="image/*"
                            capture="environment" required>
                        <small class="text-muted">Format JPG, JPEG, PNG, atau WEBP. Maksimal 10 MB.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label admin-form-label">Bukti Pembayaran</label>
                        <input type="file" name="bukti" class="form-control" accept="image/*" capture="environment">
                        <small class="text-muted">Opsional. Format JPG, JPEG, PNG, atau WEBP. Maksimal 10 MB.</small>
                    </div>
                    {{-- Product --}}
                    <div class="col-md-6">
                        <label class="form-label admin-form-label">Pilih Produk</label>
                        <select name="product_id" id="product_id" class="form-select" required>
                            <option value="">-- Pilih Produk --</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ $p->sku ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Variant --}}
                    <div class="col-md-6">
                        <label class="form-label admin-form-label">Pilih Varian</label>
                        <select name="variant_id" id="variant_id" class="form-select" required disabled>
                            <option value="">-- Pilih Produk dulu --</option>
                        </select>
                        <small id="variant_help" class="text-muted"></small>
                    </div>

                    {{-- Dates --}}
                    <div class="col-md-3">
                        <label class="form-label admin-form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}"
                            min="{{ $today }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label admin-form-label">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}"
                            min="{{ $minimumEndDate }}" required>
                        <small id="date_help" class="text-danger"></small>
                    </div>

                    {{-- Address --}}
                    <div class="col-md-6">
                        <label class="form-label admin-form-label">Alamat</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address') }}" required>
                    </div>

                    {{-- Pricing info --}}
                    <div class="col-12">
                        <div class="alert alert-light border rounded-4 mb-0">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <div>
                                    <strong>Harga/Hari:</strong> <span id="price_per_day">-</span>
                                </div>
                                <div>
                                    <strong>Stok:</strong> <span id="stock_info">-</span>
                                </div>
                                <div>
                                    <strong>Total Perkiraan:</strong> <span id="total_estimate">-</span>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Total dihitung otomatis (rent_days x price_per_day). Final total akan tersimpan sesuai
                                controller.
                            </small>
                        </div>
                    </div>


                    {{-- Payment method --}}
                    <div class="col-md-6">
                        <label for="payment_method" class="form-label admin-form-label">
                            Metode Pembayaran <span class="text-danger">*</span>
                        </label>

                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>
                                Tunai
                            </option>
                            <option value="qris_dummy" {{ old('payment_method') === 'qris_dummy' ? 'selected' : '' }}>
                                QRIS Dummy
                            </option>
                        </select>

                        @error('payment_method')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror

                        <small class="text-muted">
                            Pilih Tunai untuk pembayaran langsung, atau QRIS Dummy untuk simulasi pembayaran QR.
                        </small>
                    </div>

                    {{-- Cash payment --}}
                    <div class="col-md-6" id="cash_payment_wrapper">
                        <label for="nominal_diterima" class="form-label admin-form-label">
                            Nominal Diterima <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="nominal_diterima" id="nominal_diterima" class="form-control"
                                min="0" step="1000" value="{{ old('nominal_diterima') }}"
                                placeholder="Contoh: 150000">
                        </div>

                        @error('nominal_diterima')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror

                        <small class="text-muted">
                            Wajib diisi untuk pembayaran tunai. Nominal harus sama atau lebih besar dari total
                            pembayaran.
                        </small>
                    </div>

                    {{-- Submit --}}
                    <div class="col-12">
                        <div class="admin-form-actions">
                            <button type="submit" id="offline_submit" class="btn btn-dark rounded-pill px-4">
                                <i class="bi bi-save me-1"></i> Simpan Pesanan Offline
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>


        @php
            // Build a product-to-variants map for JavaScript. Safe to pass with @json.
            $productVariantsMap = $products->mapWithKeys(function ($p) {
                return [
                    (string) $p->id => $p->variants
                        ->map(function ($v) {
                            return [
                                'id' => $v->id,
                                'label' => trim(($v->size ? $v->size : '-') . ' / ' . ($v->color ? $v->color : '-')),
                                'price' => (float) $v->price,
                                'stock' => (int) $v->stock,
                                'status' => $v->status,
                            ];
                        })
                        ->values(),
                ];
            });
        @endphp

        <script>
            const variantsByProduct = @json($productVariantsMap);

            const productSelect = document.getElementById('product_id');
            const variantSelect = document.getElementById('variant_id');

            const priceEl = document.getElementById('price_per_day');
            const stockEl = document.getElementById('stock_info');
            const totalEl = document.getElementById('total_estimate');
            const helpEl = document.getElementById('variant_help');
            const dateHelpEl = document.getElementById('date_help');
            const submitButton = document.getElementById('offline_submit');

            const startInput = document.querySelector('input[name="start_date"]');
            const endInput = document.querySelector('input[name="end_date"]');
            const paymentMethodSelect = document.getElementById('payment_method');
            const cashPaymentWrapper = document.getElementById('cash_payment_wrapper');
            const nominalDiterimaInput = document.getElementById('nominal_diterima');

            function formatRupiah(n) {
                if (typeof n !== 'number' || isNaN(n)) return '-';
                return 'Rp' + n.toLocaleString('id-ID');
            }

            function calcDays() {
                const s = startInput.value;
                const e = endInput.value;
                if (!s || !e) return null;

                const start = new Date(s);
                const end = new Date(e);
                const diff = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;

                if (isNaN(diff) || diff <= 0) return null;
                return diff;
            }

            function updateEstimate() {
                const selectedOption = variantSelect.options[variantSelect.selectedIndex];
                if (!selectedOption || !selectedOption.dataset.price) {
                    priceEl.textContent = '-';
                    stockEl.textContent = '-';
                    totalEl.textContent = '-';
                    return;
                }

                const price = Number(selectedOption.dataset.price);
                const stock = Number(selectedOption.dataset.stock);

                priceEl.textContent = formatRupiah(price);
                stockEl.textContent = String(stock);

                const days = calcDays();
                if (!days) {
                    totalEl.textContent = '-';
                    return;
                }

                totalEl.textContent = formatRupiah(price * days) + ' (' + days + ' hari)';
            }

            function setSubmitDisabled(disabled) {
                if (submitButton) {
                    submitButton.disabled = disabled;
                }
            }

            function updatePaymentMethodUI() {
                if (!paymentMethodSelect || !cashPaymentWrapper || !nominalDiterimaInput) return;

                const isCash = paymentMethodSelect.value === 'cash';

                cashPaymentWrapper.style.display = isCash ? '' : 'none';
                nominalDiterimaInput.required = isCash;

                if (!isCash) {
                    nominalDiterimaInput.value = '';
                }
            }

            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', updatePaymentMethodUI);
                updatePaymentMethodUI();
            }

            function updateDateMinimum() {
                if (!startInput || !endInput) return;

                endInput.min = startInput.value || startInput.min;

                if (dateHelpEl) {
                    dateHelpEl.textContent = '';
                }

                if (endInput.value && startInput.value && endInput.value < startInput.value) {
                    endInput.value = startInput.value;

                    if (dateHelpEl) {
                        dateHelpEl.textContent = 'Tanggal selesai disesuaikan agar tidak sebelum tanggal mulai.';
                    }
                }

                updateEstimate();
            }

            function loadVariants(productId) {
                variantSelect.innerHTML = '';
                helpEl.textContent = '';
                priceEl.textContent = '-';
                stockEl.textContent = '-';
                totalEl.textContent = '-';
                setSubmitDisabled(false);

                if (!productId || !variantsByProduct[productId]) {
                    variantSelect.disabled = true;
                    variantSelect.innerHTML = '<option value="">-- Pilih Produk dulu --</option>';
                    return;
                }

                const variants = variantsByProduct[productId];
                variantSelect.disabled = false;

                if (variants.length === 0) {
                    variantSelect.innerHTML = '<option value="">-- Tidak ada varian --</option>';
                    helpEl.textContent = 'Produk ini belum memiliki varian.';
                    setSubmitDisabled(true);
                    return;
                }

                variantSelect.innerHTML = '<option value="">-- Pilih Varian --</option>';

                variants.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.id;
                    opt.textContent = v.label + ' | ' + formatRupiah(v.price) + ' | ' + (v.stock > 0 ? 'Stok: ' + v
                        .stock : 'Stok habis') + ' | ' + v.status;
                    opt.disabled = v.stock <= 0;

                    opt.dataset.price = v.price;
                    opt.dataset.stock = v.stock;
                    opt.dataset.status = v.status;

                    variantSelect.appendChild(opt);
                });

                const availableVariants = variants.filter(v => v.stock > 0);
                if (availableVariants.length === 0) {
                    helpEl.textContent =
                        'Semua varian produk ini sedang stok habis. Pilih produk lain sebelum menyimpan pesanan.';
                    setSubmitDisabled(true);
                    return;
                }

                helpEl.textContent = 'Varian dengan stok habis tidak dapat dipilih.';
            }

            productSelect.addEventListener('change', function() {
                loadVariants(this.value);
            });

            variantSelect.addEventListener('change', updateEstimate);
            startInput.addEventListener('change', updateDateMinimum);
            endInput.addEventListener('change', updateDateMinimum);

            // Restore old value
            const oldProductId = "{{ old('product_id') }}";
            const oldVariantId = "{{ old('variant_id') }}";

            if (oldProductId) {
                productSelect.value = oldProductId;
                loadVariants(oldProductId);

                if (oldVariantId) {
                    setTimeout(() => {
                        variantSelect.value = oldVariantId;
                        updateEstimate();
                    }, 0);
                }
            }

            updateDateMinimum();
        </script>
    </div>
@endsection
