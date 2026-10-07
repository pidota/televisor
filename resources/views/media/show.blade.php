@extends('layouts.app')

@section('title', $media->name)
@section('page-title', $media->name)
@section('page-subtitle', $media->original_filename)

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route($media->type === \App\Enums\MediaAssetType::Video ? 'content.videos' : 'content.images') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Biblioteca
        </a>
        @can('update', $media)
            <a href="{{ route('media.edit', $media) }}" class="btn btn-outline-primary btn-sm">Editar</a>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if ($media->status === \App\Enums\MediaAssetStatus::Ready)
                        @if ($media->type === \App\Enums\MediaAssetType::Image)
                            <img src="{{ route('media.preview', $media) }}" class="img-fluid rounded" alt="{{ $media->name }}">
                        @else
                            <video controls class="w-100 rounded" preload="metadata">
                                <source src="{{ route('media.preview', $media) }}" type="{{ $media->mime_type }}">
                            </video>
                        @endif
                    @else
                        <p class="text-muted mb-0">Vista previa no disponible (estado: {{ $media->status->value }}).</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5">Tipo</dt>
                        <dd class="col-7 text-capitalize">{{ $media->type->value }}</dd>
                        <dt class="col-5">Estado</dt>
                        <dd class="col-7">{{ $media->status->value }}</dd>
                        <dt class="col-5">Tamaño</dt>
                        <dd class="col-7">{{ number_format($media->size_bytes / 1024 / 1024, 2, ',', '.') }} MB</dd>
                        <dt class="col-5">MIME</dt>
                        <dd class="col-7"><code>{{ $media->mime_type }}</code></dd>
                        @if ($media->duration_seconds)
                            <dt class="col-5">Duración</dt>
                            <dd class="col-7">{{ gmdate('H:i:s', $media->duration_seconds) }}</dd>
                        @endif
                        @if ($media->width)
                            <dt class="col-5">Resolución</dt>
                            <dd class="col-7">{{ $media->width }}×{{ $media->height }}</dd>
                        @endif
                        <dt class="col-5">SHA-256</dt>
                        <dd class="col-7"><code class="small">{{ $media->checksum_sha256 ? \Illuminate\Support\Str::limit($media->checksum_sha256, 20) : '—' }}</code></dd>
                        <dt class="col-5">Subido por</dt>
                        <dd class="col-7">{{ $media->uploader?->name ?? '—' }}</dd>
                        <dt class="col-5">Fecha</dt>
                        <dd class="col-7">{{ $media->created_at?->locale('es')->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            @can('delete', $media)
                <form method="POST" action="{{ route('media.destroy', $media) }}" data-confirm="¿Eliminar este archivo? Se borrará del almacenamiento.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Eliminar archivo</button>
                </form>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Confirmar',
                text: form.getAttribute('data-confirm'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d3b66',
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
            }).then(function (r) { if (r.isConfirmed) form.submit(); });
        });
    });
</script>
@endpush
