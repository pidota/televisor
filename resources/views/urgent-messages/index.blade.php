@extends('layouts.app')

@section('title', 'Mensajes urgentes')
@section('page-title', 'Mensajes urgentes')
@section('page-subtitle', 'Cinta inferior sobre videos o pantalla completa de emergencia')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-4">
        @can('create', \App\Models\UrgentMessage::class)
            <a href="{{ route('urgent-messages.create') }}" class="btn btn-danger btn-sm">
                <i class="bi bi-exclamation-triangle"></i> Nuevo mensaje
            </a>
        @endcan
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">Todos los estados</option>
                @foreach (['draft' => 'Borrador', 'active' => 'Activo', 'cancelled' => 'Cancelado', 'expired' => 'Expirado'] as $val => $label)
                    <option value="{{ $val }}" @selected($filters['status'] === $val)>{{ $label }}</option>
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
                        <th>Título</th>
                        <th>Vigencia</th>
                        <th>Alcance</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $msg)
                        <tr>
                            <td><strong>{{ $msg->title }}</strong></td>
                            <td class="small">
                                {{ $msg->starts_at->timezone($timezone)->format('d/m/Y H:i') }}
                                —
                                {{ $msg->ends_at->timezone($timezone)->format('d/m/Y H:i') }}
                            </td>
                            <td class="small">
                                @if ($msg->applies_to_all_screens)
                                    Todas las pantallas
                                @else
                                    {{ $msg->targets_count }} pantalla(s)
                                @endif
                            </td>
                            <td>{{ $msg->priority }}</td>
                            <td><span class="badge text-bg-light border text-capitalize">{{ $msg->status->value }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('urgent-messages.show', $msg) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay mensajes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($messages->hasPages())
            <div class="card-footer bg-white">{{ $messages->links() }}</div>
        @endif
    </div>
@endsection
