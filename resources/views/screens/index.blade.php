@extends('layouts.app')

@section('title', 'Pantallas')
@section('page-title', 'Pantallas')
@section('page-subtitle', 'Administración y vinculación de dispositivos')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
        @can('manage-content')
            <a href="{{ route('screens.pair.create') }}" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">
                <i class="bi bi-link-45deg me-1"></i> Vincular pantalla
            </a>
        @else
            <span></span>
        @endcan

        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Buscar nombre, ubicación…" style="min-width: 200px;">
            <select name="status" class="form-select form-select-sm">
                <option value="">Estado registro</option>
                @foreach (['pending' => 'Pendiente', 'active' => 'Activa', 'disabled' => 'Deshabilitada', 'revoked' => 'Revocada'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="connection" class="form-select form-select-sm">
                <option value="">Conexión</option>
                <option value="online" @selected($filters['connection'] === 'online')>Online</option>
                <option value="offline" @selected($filters['connection'] === 'offline')>Offline</option>
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
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Conexión</th>
                        <th>Última conexión</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($screens as $screen)
                        <tr>
                            <td>
                                <strong>{{ $screen->name ?? 'Sin nombre' }}</strong>
                                @if ($screen->status->value === 'pending')
                                    <div class="small text-muted">UUID: {{ \Illuminate\Support\Str::limit($screen->uuid, 13) }}</div>
                                @endif
                            </td>
                            <td>{{ $screen->location ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-light border text-capitalize">{{ $screen->status->value }}</span>
                            </td>
                            <td>@include('partials.connection-badge', ['label' => $screen->connection_label])</td>
                            <td class="small text-muted">
                                @if ($screen->last_seen_at)
                                    {{ $screen->last_seen_at->locale('es')->diffForHumans() }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('screens.show', $screen) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                No hay pantallas registradas.
                                @can('manage-content')
                                    <a href="{{ route('screens.pair.create') }}">Vincule la primera pantalla</a>.
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($screens->hasPages())
            <div class="card-footer bg-white">{{ $screens->links() }}</div>
        @endif
    </div>
@endsection
