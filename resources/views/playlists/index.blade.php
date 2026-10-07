@extends('layouts.app')

@section('title', 'Playlists')
@section('page-title', 'Playlists')
@section('page-subtitle', 'Secuencias de contenido para pantallas')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
        @can('create', \App\Models\Playlist::class)
            <a href="{{ route('playlists.create') }}" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">
                <i class="bi bi-plus-lg me-1"></i> Nueva playlist
            </a>
        @endcan

        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Buscar…">
            <select name="status" class="form-select form-select-sm">
                <option value="">Estado</option>
                @foreach (['draft' => 'Borrador', 'active' => 'Activa', 'archived' => 'Archivada'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Elementos</th>
                        <th>Revisión</th>
                        <th>Actualizada</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($playlists as $playlist)
                        <tr>
                            <td><strong>{{ $playlist->name }}</strong></td>
                            <td><span class="badge text-bg-light border text-capitalize">{{ $playlist->status->value }}</span></td>
                            <td>{{ $playlist->items_count }}</td>
                            <td><code>v{{ $playlist->revision }}</code></td>
                            <td class="small text-muted">{{ $playlist->updated_at?->locale('es')->diffForHumans() }}</td>
                            <td class="text-end">
                                <a href="{{ route('playlists.show', $playlist) }}" class="btn btn-sm btn-outline-primary">Editar contenido</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay playlists.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($playlists->hasPages())
            <div class="card-footer bg-white">{{ $playlists->links() }}</div>
        @endif
    </div>
@endsection
