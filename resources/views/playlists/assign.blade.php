@extends('layouts.app')

@section('title', 'Asignar playlist')
@section('page-title', 'Asignar playlist')
@section('page-subtitle', $playlist->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('playlists.show', $playlist) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver a la playlist
        </a>
    </div>

    <form method="POST" action="{{ route('playlists.assign.update', $playlist) }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <strong>Pantallas</strong>
                        <span class="text-muted small ms-2">Playlist por defecto cuando no hay programación urgente</span>
                    </div>
                    <div class="card-body p-0" style="max-height: 420px; overflow-y: auto;">
                        @forelse ($screens as $screen)
                            <label class="list-group-item list-group-item-action d-flex align-items-start gap-2 mb-0 border-0 border-bottom rounded-0">
                                <input type="checkbox" class="form-check-input mt-1" name="screens[]" value="{{ $screen->id }}"
                                    @checked(in_array($screen->id, old('screens', $assignedScreenIds), true))>
                                <span>
                                    <strong>{{ $screen->name ?? 'Sin nombre' }}</strong>
                                    @if ($screen->location)
                                        <span class="d-block small text-muted">{{ $screen->location }}</span>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <p class="text-muted small p-3 mb-0">No hay pantallas activas vinculadas.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <strong>Grupos de pantallas</strong>
                    </div>
                    <div class="card-body p-0" style="max-height: 420px; overflow-y: auto;">
                        @forelse ($groups as $group)
                            <label class="list-group-item list-group-item-action d-flex align-items-center gap-2 mb-0 border-0 border-bottom rounded-0">
                                <input type="checkbox" class="form-check-input" name="groups[]" value="{{ $group->id }}"
                                    @checked(in_array($group->id, old('groups', $assignedGroupIds), true))>
                                <strong>{{ $group->name }}</strong>
                            </label>
                        @empty
                            <p class="text-muted small p-3 mb-0">
                                No hay grupos.
                                @can('create', \App\Models\ScreenGroup::class)
                                    <a href="{{ route('screen-groups.create') }}">Crear grupo</a>
                                @endcan
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">
                Guardar asignación
            </button>
            <p class="small text-muted align-self-center mb-0 ms-2">
                Al guardar se incrementa <code>manifest_version</code> en las pantallas afectadas.
            </p>
        </div>
    </form>
@endsection
