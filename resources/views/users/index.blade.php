@extends('layouts.admin')

@section('content')
    <div class="admin-page admin-page--wide">

        <div class="admin-page-header">
            <div>
                <span class="admin-page-eyebrow">Administrasi</span>
                <h1 class="admin-page-title">Manajemen Pengguna</h1>
                <p class="admin-page-subtitle">Kelola hak akses dan akun pengguna Sewana.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('pemilik.users.index') }}" class="admin-toolbar search-card filter-card search-form">
            <div class="input-group admin-search-box search-input-wrap">
                <span class="input-group-text bg-transparent border-0 ps-4 text-muted">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="form-control border-0 shadow-none py-2 bg-transparent search-input" placeholder="Cari nama atau email...">

            </div>
            <div class="admin-toolbar-actions">
                @if (request('search'))
                    <a href="{{ route('pemilik.users.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                        Reset
                    </a>
                @endif
                <button class="btn btn-dark rounded-pill px-4 fw-bold search-button" type="submit">Cari</button>
            </div>
        </form>

        <div class="row g-4">
            @forelse ($users as $user)
                @php
                    $roleName = strtolower($user->getRoleNames()->first() ?? '');

                    // Automatic color logic.
                    $roleColor = match ($roleName) {
                        'pemilik' => 'primary',
                        'pegawai' => 'info',
                        'penyewa' => 'success',
                        default => 'secondary',
                    };
                @endphp

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 user-card-hover">
                        <div class="card-body p-4 d-flex flex-column">

                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar-circle shadow-sm me-3">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="overflow-hidden">
                                    <h6 class="fw-bold text-dark mb-0 text-truncate" title="{{ $user->name }}">
                                        {{ $user->name }}
                                    </h6>
                                    <small class="text-muted text-truncate d-block">
                                        {{ $user->email }}
                                    </small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Hak Akses</span>
                                    <span
                                        class="badge bg-{{ $roleColor }} bg-opacity-10 text-{{ $roleColor }} border border-{{ $roleColor }} border-opacity-25 rounded-pill px-3">
                                        {{ ucfirst($roleName ?: 'Belum ada') }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Bergabung</span>
                                    <span class="text-dark small fw-medium">
                                        <i class="bi bi-calendar-event me-1 text-muted"></i>
                                        {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <hr class="text-muted opacity-25 mt-0 mb-3">
                                <form action="{{ route('pemilik.users.updateRole', $user->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <label class="admin-mini-label mb-2">Ubah Peran</label>
                                    <div class="input-group input-group-sm">
                                        <select name="role"
                                            class="form-select border-secondary border-opacity-25 shadow-none text-secondary bg-light">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->name }}"
                                                    {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                    {{ ucfirst($role->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-dark px-3 fw-bold">
                                            Simpan
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

            @empty
                <div class="col-12 text-center py-5">
                    <div class="admin-empty-state">
                        <i class="bi bi-people fs-1 text-muted opacity-25 d-block mb-3"></i>
                        <h6 class="text-dark fw-bold mb-1">Pengguna tidak ditemukan</h6>
                        <p class="text-muted small mb-0">Coba gunakan kata kunci pencarian yang lain.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="admin-pagination">
            {{ $users->withQueryString()->links('pagination::bootstrap-5') }}
        </div>

    </div>
@endsection

