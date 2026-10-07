@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)
@section('page-subtitle', 'Biblioteca multimedia municipal')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
        @can('create', \App\Models\MediaAsset::class)
            <button type="button" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-cloud-upload me-1"></i> Subir {{ $type === \App\Enums\MediaAssetType::Video ? 'video' : 'imagen' }}
            </button>
        @endcan

        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Buscar…" style="min-width: 180px;">
            <select name="status" class="form-select form-select-sm">
                <option value="">Estado</option>
                @foreach (['ready' => 'Listo', 'processing' => 'Procesando', 'failed' => 'Error'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
        </form>
    </div>

    <div class="row g-3">
        @forelse ($assets as $asset)
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    @if ($asset->type === \App\Enums\MediaAssetType::Image && $asset->status === \App\Enums\MediaAssetStatus::Ready)
                        <img src="{{ route('media.preview', $asset) }}" class="card-img-top object-fit-cover" alt="" style="height: 140px;">
                    @else
                        <div class="bg-dark bg-opacity-10 d-flex align-items-center justify-content-center" style="height: 140px;">
                            <i class="bi bi-{{ $asset->type->value === 'video' ? 'camera-video' : 'image' }} display-6 text-muted"></i>
                        </div>
                    @endif
                    <div class="card-body p-3">
                        <h3 class="h6 mb-1 text-truncate" title="{{ $asset->name }}">{{ $asset->name }}</h3>
                        <p class="small text-muted mb-2 text-truncate">{{ $asset->original_filename }}</p>
                        <div class="small mb-2">
                            <span class="badge text-bg-light border">{{ strtoupper($asset->extension) }}</span>
                            <span class="text-muted">{{ number_format($asset->size_bytes / 1024 / 1024, 2, ',', '.') }} MB</span>
                        </div>
                        @if ($asset->type === \App\Enums\MediaAssetType::Video && $asset->duration_seconds)
                            <p class="small text-muted mb-2">Duración: {{ gmdate('i:s', $asset->duration_seconds) }} min</p>
                        @endif
                        @if ($asset->width && $asset->height)
                            <p class="small text-muted mb-2">{{ $asset->width }}×{{ $asset->height }}</p>
                        @endif
                        <a href="{{ route('media.show', $asset) }}" class="btn btn-sm btn-outline-primary w-100">Detalle</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-5">
                        No hay archivos en esta biblioteca.
                        @can('create', \App\Models\MediaAsset::class)
                            Use <strong>Subir</strong> para agregar el primero.
                        @endcan
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if ($assets->hasPages())
        <div class="mt-3">{{ $assets->links() }}</div>
    @endif

    @can('create', \App\Models\MediaAsset::class)
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route($storeRoute) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="uploadModalLabel">Subir {{ $type === \App\Enums\MediaAssetType::Video ? 'video MP4' : 'imagen' }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre visible (opcional)</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label for="file" class="form-label">Archivo</label>
                                <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" required
                                       accept="{{ $type === \App\Enums\MediaAssetType::Video ? '.mp4,video/mp4' : '.jpg,.jpeg,.png,.webp,image/*' }}">
                                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    @if ($type === \App\Enums\MediaAssetType::Video)
                                        Solo MP4. Máx. {{ app(\App\Support\SettingStore::class)->getInt('media.max_upload_mb', 512) }} MB.
                                    @else
                                        JPG, PNG o WebP. Máx. {{ app(\App\Support\SettingStore::class)->getInt('media.max_upload_mb', 512) }} MB.
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">Subir</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@if ($errors->has('file'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('uploadModal')).show();
            });
        </script>
    @endpush
@endif
