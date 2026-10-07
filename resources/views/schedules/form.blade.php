@extends('layouts.app')

@section('title', $mode === 'create' ? 'Nueva programación' : 'Editar programación')
@section('page-title', $mode === 'create' ? 'Nueva programación' : 'Editar programación')
@section('page-subtitle', 'Fechas interpretadas en {{ $timezone }}')

@section('content')
    @php
        $startsLocal = old('starts_at', $schedule->starts_at
            ? $schedule->starts_at->timezone($timezone)->format('Y-m-d\TH:i')
            : '');
        $endsLocal = old('ends_at', $schedule->ends_at
            ? $schedule->ends_at->timezone($timezone)->format('Y-m-d\TH:i')
            : '');
        $useTimeRules = old('use_time_rules', $useTimeRules ?? false);
        $selectedDays = old('days', $timeRuleDays ?? [1,2,3,4,5]);
    @endphp

    <form method="POST" action="{{ $mode === 'create' ? route('schedules.store') : route('schedules.update', $schedule) }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre campaña</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $schedule->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="playlist_id" class="form-label">Playlist</label>
                            <select name="playlist_id" id="playlist_id" class="form-select @error('playlist_id') is-invalid @enderror" required>
                                <option value="">Seleccione…</option>
                                @foreach ($playlists as $pl)
                                    <option value="{{ $pl->id }}" @selected(old('playlist_id', $schedule->playlist_id) == $pl->id)>{{ $pl->name }}</option>
                                @endforeach
                            </select>
                            @error('playlist_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="starts_at" class="form-label">Inicio</label>
                                <input type="datetime-local" name="starts_at" id="starts_at" class="form-control @error('starts_at') is-invalid @enderror" value="{{ $startsLocal }}" required>
                                @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ends_at" class="form-label">Fin</label>
                                <input type="datetime-local" name="ends_at" id="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ $endsLocal }}" required>
                                @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Prioridad</label>
                                <input type="number" name="priority" id="priority" class="form-control" min="0" max="999"
                                       value="{{ old('priority', $schedule->priority ?? 0) }}">
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1"
                                        @checked(old('is_active', $schedule->is_active ?? true))>
                                    <label class="form-check-label" for="is_active">Programación activa</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="use_time_rules" id="use_time_rules" value="1"
                                @checked($useTimeRules)>
                            <label class="form-check-label" for="use_time_rules"><strong>Limitar a franja horaria semanal</strong></label>
                        </div>
                    </div>
                    <div class="card-body" id="timeRulesPanel">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach ($weekdays as $num => $label)
                                <label class="btn btn-sm btn-outline-secondary">
                                    <input type="checkbox" class="d-none weekday-check" name="days[]" value="{{ $num }}"
                                        @checked(in_array($num, $selectedDays, true))> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label for="start_time" class="form-label">Desde</label>
                                <input type="time" name="start_time" id="start_time" class="form-control"
                                       value="{{ old('start_time', $timeStart ?? '08:00') }}">
                            </div>
                            <div class="col-6">
                                <label for="end_time" class="form-label">Hasta</label>
                                <input type="time" name="end_time" id="end_time" class="form-control"
                                       value="{{ old('end_time', $timeEnd ?? '17:30') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white"><strong>Pantallas</strong></div>
                    <div class="card-body p-0" style="max-height: 280px; overflow-y: auto;">
                        @foreach ($screens as $screen)
                            <label class="list-group-item d-flex gap-2 border-0 border-bottom rounded-0">
                                <input type="checkbox" name="screens[]" value="{{ $screen->id }}"
                                    @checked(in_array($screen->id, old('screens', $assignedScreenIds ?? []), true))>
                                <span>{{ $screen->name }} <small class="text-muted">{{ $screen->location }}</small></span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><strong>Grupos</strong></div>
                    <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                        @foreach ($groups as $group)
                            <label class="list-group-item d-flex gap-2 border-0 border-bottom rounded-0">
                                <input type="checkbox" name="groups[]" value="{{ $group->id }}"
                                    @checked(in_array($group->id, old('groups', $assignedGroupIds ?? []), true))>
                                {{ $group->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">Guardar</button>
            <a href="{{ $mode === 'edit' ? route('schedules.show', $schedule) : route('schedules.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
@endsection

@push('styles')
<style>
    .weekday-check:checked + *,
    label:has(.weekday-check:checked) { background-color: #0d3b66; color: #fff; border-color: #0d3b66 !important; }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const master = document.getElementById('use_time_rules');
        const panel = document.getElementById('timeRulesPanel');
        function toggle() {
            panel.style.opacity = master.checked ? '1' : '0.5';
            panel.querySelectorAll('input').forEach(function (el) {
                if (el !== master) el.disabled = !master.checked;
            });
        }
        master?.addEventListener('change', toggle);
        toggle();
        document.querySelectorAll('label.btn').forEach(function (lbl) {
            lbl.addEventListener('click', function (e) {
                const cb = lbl.querySelector('.weekday-check');
                if (e.target !== cb) {
                    cb.checked = !cb.checked;
                }
            });
        });
    })();
</script>
@endpush
