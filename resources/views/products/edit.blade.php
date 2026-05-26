@extends('layouts.admin')

@section('title', 'Edit Produk - Sewana')
@section('meta_description', 'Perbarui detail produk, foto, dan varian stok Sewana.')

@section('content')
    @php
        $productImages = $product->images;
    @endphp

    <div class="admin-page">
        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Inventaris</span>
                <h1 class="admin-page-title">Edit Produk</h1>
                <p class="admin-page-subtitle">Perbarui detail produk, foto, dan varian stok untuk katalog Sewana.</p>
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

        <form action="{{ route('pegawai.products.update', $product->id) }}" method="POST" enctype="multipart/form-data"
            class="admin-card">
            @csrf
            @method('PUT')

            <div class="admin-card-header">
                <span class="admin-page-eyebrow">Detail Produk</span>
                <h5 class="fw-bold text-dark mb-0 mt-1">{{ $product->name }}</h5>
            </div>

            <div class="admin-card-body">
                <div class="admin-form-grid">
                    <div>
                        <label class="form-label admin-form-label" for="product-name">Nama Produk</label>
                        <input type="text" id="product-name" name="name" class="form-control"
                            value="{{ old('name', $product->name) }}" required>
                    </div>

                    <div>
                        <label class="form-label admin-form-label" for="product-status">Status Produk</label>
                        <select name="status" id="product-status" class="form-select" required>
                            <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>
                                Aktif
                            </option>
                            <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>
                                Nonaktif
                            </option>
                        </select>
                    </div>

                    <div class="full-span">
                        <label class="form-label admin-form-label" for="product-description">Deskripsi</label>
                        <textarea name="description" id="product-description" class="form-control" rows="4">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="full-span">
                        <label class="form-label admin-form-label" for="product-images">Foto Produk</label>
                        <input type="file" id="product-images" name="images[]" class="form-control"
                            accept="image/*" multiple>
                        <div class="admin-form-help mt-1">Format JPG, JPEG, PNG, atau WEBP. Maksimal 10 MB per file, hingga 10 gambar baru.</div>
                        @error('images')
                            <div class="admin-field-error">{{ $message }}</div>
                        @enderror
                        @error('images.*')
                            <div class="admin-field-error">{{ $message }}</div>
                        @enderror

                        @if ($productImages->isNotEmpty())
                            <div class="mt-3 d-flex flex-wrap gap-3">
                                @foreach ($productImages as $imageIndex => $img)
                                    <div class="admin-variant-item text-center">
                                        @if ($img->existsOnPublicDisk())
                                            <img src="{{ $img->publicUrl() }}"
                                                class="admin-image-thumb img-thumbnail"
                                                alt="Foto produk {{ $product->name }}"
                                                width="100" height="100"
                                                @if ($imageIndex === 0) fetchpriority="high" @else loading="lazy" @endif
                                                decoding="async">
                                        @else
                                            <div class="admin-image-thumb img-thumbnail d-flex align-items-center justify-content-center text-muted small">
                                                File hilang
                                            </div>
                                        @endif
                                        <div class="form-check mt-2 mb-0">
                                            <input type="checkbox" name="delete_images[]" value="{{ $img->id }}"
                                                class="form-check-input" id="delete-image-{{ $img->id }}">
                                            <label for="delete-image-{{ $img->id }}" class="form-check-label small">
                                                Hapus
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
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

                <div id="variants" class="d-grid gap-3">
                    @foreach ($product->variants as $index => $variant)
                        <div class="variant-row admin-variant-item">
                            <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-2">
                                    <input type="text" name="variants[{{ $index }}][size]" class="form-control"
                                        value="{{ $variant->size }}" placeholder="Ukuran"
                                        aria-label="Ukuran varian {{ $index + 1 }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="variants[{{ $index }}][color]" class="form-control"
                                        value="{{ $variant->color }}" placeholder="Warna"
                                        aria-label="Warna varian {{ $index + 1 }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="variants[{{ $index }}][price]" class="form-control"
                                        value="{{ $variant->price }}" placeholder="Harga" required
                                        aria-label="Harga varian {{ $index + 1 }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="variants[{{ $index }}][stock]" class="form-control"
                                        value="{{ $variant->stock }}" placeholder="Stok" required
                                        aria-label="Stok varian {{ $index + 1 }}">
                                </div>
                                <div class="col-md-3">
                                    <select name="variants[{{ $index }}][status]" class="form-select" required
                                        aria-label="Status varian {{ $index + 1 }}">
                                        <option value="tersedia" {{ $variant->status == 'tersedia' ? 'selected' : '' }}>
                                            Tersedia
                                        </option>
                                        <option value="disewa" {{ $variant->status == 'disewa' ? 'selected' : '' }}>
                                            Disewa
                                        </option>
                                        <option value="rusak" {{ $variant->status == 'rusak' ? 'selected' : '' }}>
                                            Rusak
                                        </option>
                                        <option value="hilang" {{ $variant->status == 'hilang' ? 'selected' : '' }}>
                                            Hilang
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-1 text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-variant"
                                        title="Hapus varian" aria-label="Hapus varian {{ $index + 1 }}">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="admin-form-actions">
                    <a href="{{ route('pegawai.products.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Perbarui Produk
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let variantIndex = {{ $product->variants->count() }};
        document.getElementById('add-variant').addEventListener('click', function() {
            const container = document.getElementById('variants');
            const row = document.createElement('div');
            row.classList.add('variant-row', 'admin-variant-item');
            row.innerHTML = `
            <div class="row g-3 align-items-center">
                <div class="col-md-2"><input type="text" name="variants[${variantIndex}][size]" class="form-control" placeholder="Ukuran" aria-label="Ukuran varian ${variantIndex + 1}"></div>
                <div class="col-md-2"><input type="text" name="variants[${variantIndex}][color]" class="form-control" placeholder="Warna" aria-label="Warna varian ${variantIndex + 1}"></div>
                <div class="col-md-2"><input type="number" name="variants[${variantIndex}][price]" class="form-control" placeholder="Harga" required aria-label="Harga varian ${variantIndex + 1}"></div>
                <div class="col-md-2"><input type="number" name="variants[${variantIndex}][stock]" class="form-control" placeholder="Stok" required aria-label="Stok varian ${variantIndex + 1}"></div>
                <div class="col-md-3">
                    <select name="variants[${variantIndex}][status]" class="form-select" required aria-label="Status varian ${variantIndex + 1}">
                        <option value="tersedia">Tersedia</option>
                        <option value="disewa">Disewa</option>
                        <option value="rusak">Rusak</option>
                        <option value="hilang">Hilang</option>
                    </select>
                </div>
                <div class="col-md-1 text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-variant" title="Hapus varian" aria-label="Hapus varian ${variantIndex + 1}"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        `;
            container.appendChild(row);
            variantIndex++;
        });

        document.addEventListener('click', function(event) {
            if (event.target.closest('.remove-variant')) {
                event.target.closest('.variant-row').remove();
            }
        });
    </script>
@endsection
