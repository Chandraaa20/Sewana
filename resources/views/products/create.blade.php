@extends('layouts.admin')

@section('content')
    <div class="admin-page">
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Inventaris</span>
                <h1 class="admin-page-title">Tambah Produk</h1>
                <p class="admin-page-subtitle">Lengkapi informasi produk, foto, dan varian stok yang akan disewakan.</p>
            </div>
            <a href="{{ route('pegawai.products.index') }}" class="btn btn-outline-dark rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger rounded-4 shadow-sm border-0">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('pegawai.products.store') }}" enctype="multipart/form-data"
            class="admin-card">
            @csrf

            <div class="admin-card-header">
                <span class="admin-page-eyebrow">Detail Produk</span>
                <h5 class="fw-bold text-dark mb-0 mt-1">Informasi Dasar</h5>
            </div>

            <div class="admin-card-body">
                <div class="admin-form-grid">
                    <div>
                        <label class="form-label admin-form-label">Nama Produk</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    <div>
                        <label class="form-label admin-form-label">Status Produk</label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>

                    <div class="full-span">
                        <label class="form-label admin-form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                    </div>

                    <div class="full-span">
                        <label class="form-label admin-form-label" for="product-images">Foto Produk</label>
                        <input type="file" id="product-images" name="images[]" class="form-control"
                            accept="image/*" capture="environment" multiple>
                        <div class="admin-form-help mt-1">Format JPG, JPEG, PNG, atau WEBP. Maksimal 10 MB per file, hingga 10 gambar.</div>
                        @error('images')
                            <div class="admin-field-error">{{ $message }}</div>
                        @enderror
                        @error('images.*')
                            <div class="admin-field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-3 mt-4 mb-3">
                    <div>
                        <span class="admin-page-eyebrow">Varian</span>
                        <h5 class="fw-bold text-dark mb-0 mt-1">Varian Produk</h5>
                    </div>
                    <button type="button" id="add-variant" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Varian
                    </button>
                </div>

                <div id="variant-container" class="d-grid gap-3">
                    <div class="variant-item admin-variant-item">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label admin-form-label" for="variant-0-size">Ukuran</label>
                                <input type="text" id="variant-0-size" name="variants[0][size]" class="form-control" placeholder="Ukuran">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label admin-form-label" for="variant-0-color">Warna</label>
                                <input type="text" id="variant-0-color" name="variants[0][color]" class="form-control" placeholder="Warna">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label admin-form-label" for="variant-0-price">Harga</label>
                                <input type="number" id="variant-0-price" name="variants[0][price]" class="form-control" placeholder="Harga"
                                    required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label admin-form-label" for="variant-0-stock">Stok</label>
                                <input type="number" id="variant-0-stock" name="variants[0][stock]" class="form-control" placeholder="Stok"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label admin-form-label" for="variant-0-status">Status</label>
                                <select name="variants[0][status]" id="variant-0-status" class="form-select" required>
                                    <option value="tersedia">Tersedia</option>
                                    <option value="disewa">Disewa</option>
                                    <option value="rusak">Rusak</option>
                                    <option value="hilang">Hilang</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="admin-form-actions">
                    <a href="{{ route('pegawai.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Simpan Produk
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let variantIndex = 1;
        document.getElementById('add-variant').addEventListener('click', function() {
            const container = document.getElementById('variant-container');
            const item = `
            <div class="variant-item admin-variant-item">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label admin-form-label" for="variant-${variantIndex}-size">Ukuran</label>
                        <input type="text" id="variant-${variantIndex}-size" name="variants[${variantIndex}][size]" class="form-control" placeholder="Ukuran">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label admin-form-label" for="variant-${variantIndex}-color">Warna</label>
                        <input type="text" id="variant-${variantIndex}-color" name="variants[${variantIndex}][color]" class="form-control" placeholder="Warna">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label admin-form-label" for="variant-${variantIndex}-price">Harga</label>
                        <input type="number" id="variant-${variantIndex}-price" name="variants[${variantIndex}][price]" class="form-control" placeholder="Harga" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label admin-form-label" for="variant-${variantIndex}-stock">Stok</label>
                        <input type="number" id="variant-${variantIndex}-stock" name="variants[${variantIndex}][stock]" class="form-control" placeholder="Stok" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label admin-form-label" for="variant-${variantIndex}-status">Status</label>
                        <select name="variants[${variantIndex}][status]" id="variant-${variantIndex}-status" class="form-select" required>
                            <option value="tersedia">Tersedia</option>
                            <option value="disewa">Disewa</option>
                            <option value="rusak">Rusak</option>
                            <option value="hilang">Hilang</option>
                        </select>
                    </div>
                </div>
            </div>`;
            container.insertAdjacentHTML('beforeend', item);
            variantIndex++;
        });
    </script>
@endsection
