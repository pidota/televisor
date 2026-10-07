@extends('layouts.app')

@section('title', 'Programación')
@section('page-title', 'Programación')
@section('page-subtitle', 'Campañas con fechas y franjas horarias · zona {{ $timezone }}')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-4">
        @can('create', \App\Models\Schedule::class)
            <a href="{{ route('schedules.create') }}" class="btn btn-primary btn-sm" style="background-color: #0d3b66; border-color: #0d3b66;">
                <i class="bi bi-plus-lg"></i> Nueva programación
            </a>
        @endcan
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" class="form-control form-control-sm" value="{{ $filters['q'] }}" placeholder="Buscar…">
            <select name="active" class="form-select form-select-sm">
                <option value="">Todas</option>
                <option value="1" @selected($filters['active'] === '1')>Activas</option>
                <option value="0" @selected($filters['active'] === '0')>Inactivas</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtrar</button>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Playlist</th>
                        <th>Vigencia (local)</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td><strong>{{ $schedule->name }}</strong></td>
                            <td>{{ $schedule->playlist?->name ?? '—' }}</td>
                            <td class="small">
                                {{ $schedule->starts_at->timezone($timezone)->format('d/m/Y H:i') }}
                                —
                                {{ $schedule->ends_at->timezone($timezone)->format('d/m/Y H:i') }}
                            </td>
                            <td>{{ $schedule->priority }}</td>
                            <td>
                                @if ($schedule->is_active)
                                    <span class="badge badge-online">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('schedules.show', $schedule) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Sin programaciones.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($schedules->hasPages())
            <div class="card-footer bg-white">{{ $schedules->links() }}</div>
        @endif
    </div>
@endsection
