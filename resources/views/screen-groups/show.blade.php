@extends('layouts.app')

@section('title', $group->name)
@section('page-title', $group->name)
@section('page-subtitle', $group->description)

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('screen-groups.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Grupos</a>
        @can('update', $group)
            <a href="{{ route('screen-groups.edit', $group) }}" class="btn btn-outline-primary btn-sm">Editar datos</a>
        @endcan
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            @can('update', $group)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><strong>Pantallas del grupo</strong></div>
                    <form method="POST" action="{{ route('screen-groups.members.sync', $group) }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                            @foreach ($allScreens as $screen)
                                <label class="list-group-item d-flex gap-2 align-items-start border-0 border-bottom rounded-0">
                                    <input type="checkbox" class="form-check-input mt-1" name="screens[]" value="{{ $screen->id }}"
                                        @checked(in_array($screen->id, old('screens', $memberIds), true))>
                                    <span>
                                        <strong>{{ $screen->name ?? 'Sin nombre' }}</strong>
                                        @if ($screen->location)
                                            <span class="d-block small text-muted">{{ $screen->location }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="card-footer bg-white">
                            <button type="submit" class="btn btn-primary btn-sm" style="background-color: #0d3b66; border-color: #0d3b66;">Guardar pantallas</button>
                        </div>
                    </form>
                </div>
            @else
                <ul class="list-group shadow-sm">
                    @forelse ($group->screens as $screen)
                        <li class="list-group-item">{{ $screen->name }} — {{ $screen->location }}</li>
                    @empty
                        <li class="list-group-item text-muted">Sin pantallas en el grupo.</li>
                    @endforelse
                </ul>
            @endcan
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><strong>Playlists asignadas al grupo</strong></div>
                <ul class="list-group list-group-flush">
                    @forelse ($group->playlistAssignments as $assignment)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="{{ route('playlists.show', $assignment->playlist) }}">{{ $assignment->playlist?->name }}</a>
                            @if ($assignment->is_default)
                                <span class="badge text-bg-primary">Default</span>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-muted small">Asigne desde la playlist → Asignar pantallas.</li>
                    @endforelse
                </ul>
            </div>

            @can('delete', $group)
                <form method="POST" action="{{ route('screen-groups.destroy', $group) }}" data-confirm="¿Eliminar este grupo?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Eliminar grupo</button>
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
