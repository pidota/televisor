@extends('layouts.app')

@section('title', $schedule->name)
@section('page-title', $schedule->name)
@section('page-subtitle', 'Programación de contenido')

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('schedules.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Listado</a>
        @can('update', $schedule)
            <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-outline-primary btn-sm">Editar</a>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Playlist</dt>
                        <dd class="col-sm-8">
                            @if ($schedule->playlist)
                                <a href="{{ route('playlists.show', $schedule->playlist) }}">{{ $schedule->playlist->name }}</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="col-sm-4">Inicio ({{ $timezone }})</dt>
                        <dd class="col-sm-8">{{ $schedule->starts_at->timezone($timezone)->format('d/m/Y H:i') }}</dd>
                        <dt class="col-sm-4">Fin ({{ $timezone }})</dt>
                        <dd class="col-sm-8">{{ $schedule->ends_at->timezone($timezone)->format('d/m/Y H:i') }}</dd>
                        <dt class="col-sm-4">Prioridad</dt>
                        <dd class="col-sm-8">{{ $schedule->priority }}</dd>
                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8">{{ $schedule->is_active ? 'Activa' : 'Inactiva' }}</dd>
                        <dt class="col-sm-4">Franja horaria</dt>
                        <dd class="col-sm-8">
                            @if ($schedule->timeRules->isEmpty())
                                Todo el día (dentro del rango de fechas)
                            @else
                                @php
                                    $labels = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];
                                    $dayText = collect($days)->map(fn ($d) => $labels[$d] ?? $d)->join(', ');
                                @endphp
                                {{ $dayText }}
                                {{ $timeSample ? substr((string)$timeSample->start_time,0,5) : '' }}
                                –
                                {{ $timeSample ? substr((string)$timeSample->end_time,0,5) : '' }}
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><strong>Destinos</strong></div>
                <ul class="list-group list-group-flush small">
                    @php
                        $screenTargets = $schedule->targets->where('target_type', \App\Enums\AssigneeType::Screen);
                        $groupTargets = $schedule->targets->where('target_type', \App\Enums\AssigneeType::ScreenGroup);
                    @endphp
                    @forelse ($screenTargets as $t)
                        @php($screen = \App\Models\Screen::find($t->target_id))
                        <li class="list-group-item"><i class="bi bi-tv me-1"></i> {{ $screen?->name ?? 'Pantalla #'.$t->target_id }}</li>
                    @empty
                    @endforelse
                    @foreach ($groupTargets as $t)
                        @php($group = \App\Models\ScreenGroup::find($t->target_id))
                        <li class="list-group-item"><i class="bi bi-collection me-1"></i> Grupo: {{ $group?->name ?? '#'.$t->target_id }}</li>
                    @endforeach
                    @if ($screenTargets->isEmpty() && $groupTargets->isEmpty())
                        <li class="list-group-item text-muted">Sin destinos</li>
                    @endif
                </ul>
            </div>
            @can('delete', $schedule)
                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" data-confirm="¿Eliminar programación?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Eliminar</button>
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
            Swal.fire({ title: 'Confirmar', text: form.getAttribute('data-confirm'), icon: 'warning', showCancelButton: true, confirmButtonColor: '#0d3b66' })
                .then(function (r) { if (r.isConfirmed) form.submit(); });
        });
    });
</script>
@endpush
