@extends('layouts.app')

@section('title', $playlist->name)
@section('page-title', $playlist->name)
@section('page-subtitle')
    {{ $playlist->items->count() }} elementos · {{ gmdate('H:i:s', max($totalDuration, 0)) }} total · revisión {{ $playlist->revision }}
@endsection

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('playlists.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Playlists</a>
        @can('update', $playlist)
            <a href="{{ route('playlists.edit', $playlist) }}" class="btn btn-outline-primary btn-sm">Datos generales</a>
            <a href="{{ route('playlists.assign.edit', $playlist) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-tv"></i> Asignar pantallas
            </a>
        @endcan
    </div>

    <div class="row g-4">
        <div class="col-lg-8 order-2 order-lg-1">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Contenidos</strong>
                    @can('update', $playlist)
                        <span class="small text-muted"><i class="bi bi-grip-vertical"></i> Arrastre para reordenar</span>
                    @endcan
                </div>
                <ul class="list-group list-group-flush" id="playlistSortable">
                    @forelse ($playlist->items as $item)
                        @php($media = $item->mediaAsset)
                        <li class="list-group-item playlist-item" data-id="{{ $item->id }}">
                            <div class="d-flex align-items-start gap-3">
                                @can('update', $playlist)
                                    <span class="text-muted sort-handle pt-1" style="cursor: grab;"><i class="bi bi-grip-vertical fs-5"></i></span>
                                @endcan
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <span class="badge text-bg-light border me-1">{{ $item->sort_order }}</span>
                                            <strong>{{ $media?->name ?? '—' }}</strong>
                                            <span class="text-muted small ms-1">{{ strtoupper($media?->extension ?? '') }}</span>
                                        </div>
                                        <span class="badge {{ $media?->type->value === 'video' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                            {{ $media?->type->value === 'video' ? 'Video' : 'Imagen' }}
                                        </span>
                                    </div>
                                    <p class="small text-muted mb-2 text-truncate">{{ $media?->original_filename }}</p>

                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <span class="small">
                                            Duración efectiva:
                                            <strong>{{ $item->effectiveDurationSeconds() }} s</strong>
                                            @if ($media?->type->value === 'video' && $media->duration_seconds)
                                                <span class="text-muted">(del video)</span>
                                            @endif
                                        </span>

                                        @can('update', $playlist)
                                            @if ($media?->type->value === 'image')
                                                <form method="POST" action="{{ route('playlists.items.update', [$playlist, $item]) }}" class="d-flex align-items-center gap-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="number" name="duration_seconds" class="form-control form-control-sm" style="width: 5rem;"
                                                           min="1" max="3600" value="{{ $item->duration_seconds ?? 10 }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">OK</button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('playlists.items.destroy', [$playlist, $item]) }}" class="ms-auto" data-confirm="¿Quitar este elemento de la playlist?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">
                            Esta playlist aún no tiene contenidos.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-4 order-1 order-lg-2">
            @can('update', $playlist)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white"><strong>Agregar contenido</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('playlists.items.store', $playlist) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="media_asset_id" class="form-label">Archivo</label>
                                <select name="media_asset_id" id="media_asset_id" class="form-select @error('media_asset_id') is-invalid @enderror" required>
                                    <option value="">Seleccione…</option>
                                    @php($grouped = $availableMedia->groupBy(fn ($m) => $m->type->value))
                                    @foreach ($grouped as $type => $items)
                                        <optgroup label="{{ $type === 'video' ? 'Videos' : 'Imágenes' }}">
                                            @foreach ($items as $media)
                                                <option value="{{ $media->id }}" @selected(old('media_asset_id') == $media->id)>
                                                    {{ $media->name }} (.{{ $media->extension }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('media_asset_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @if ($availableMedia->isEmpty())
                                    <p class="small text-muted mt-2 mb-0">Suba contenido en Videos o Imágenes primero.</p>
                                @endif
                            </div>
                            <div class="mb-3" id="durationField">
                                <label for="duration_seconds" class="form-label">Duración (imágenes, segundos)</label>
                                <input type="number" name="duration_seconds" id="duration_seconds" class="form-control" min="1" max="3600" value="{{ old('duration_seconds', 10) }}">
                                @error('duration_seconds')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-sm" style="background-color: #0d3b66; border-color: #0d3b66;" @disabled($availableMedia->isEmpty())>
                                Agregar a la playlist
                            </button>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="card border-0 shadow-sm">
                <div class="card-body small text-muted">
                    <p class="mb-1"><strong>Estado:</strong> <span class="text-capitalize">{{ $playlist->status->value }}</span></p>
                    @if ($playlist->description)
                        <p class="mb-0">{{ $playlist->description }}</p>
                    @endif
                </div>
            </div>

            @can('delete', $playlist)
                <form method="POST" action="{{ route('playlists.destroy', $playlist) }}" class="mt-3" data-confirm="¿Eliminar esta playlist?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Eliminar playlist</button>
                </form>
            @endcan
        </div>
    </div>
@endsection

@can('update', $playlist)
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            (function () {
                const list = document.getElementById('playlistSortable');
                if (!list || list.querySelectorAll('.playlist-item').length === 0) return;

                Sortable.create(list, {
                    handle: '.sort-handle',
                    animation: 150,
                    onEnd: function () {
                        const order = Array.from(list.querySelectorAll('.playlist-item')).map(function (el) {
                            return parseInt(el.getAttribute('data-id'), 10);
                        });

                        fetch(@json(route('playlists.items.reorder', $playlist)), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ order: order }),
                        }).then(function (res) {
                            if (!res.ok) {
                                Swal.fire({ icon: 'error', title: 'No se pudo guardar el orden' });
                            }
                        });
                    },
                });

                document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Confirmar',
                            text: form.getAttribute('data-confirm'),
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#0d3b66',
                            confirmButtonText: 'Sí',
                            cancelButtonText: 'Cancelar',
                        }).then(function (r) { if (r.isConfirmed) form.submit(); });
                    });
                });
            })();
        </script>
    @endpush
@endcan
