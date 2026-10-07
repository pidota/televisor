@extends('layouts.app')

@section('title', $mode === 'create' ? 'Nuevo mensaje urgente' : 'Editar mensaje')
@section('page-title', $mode === 'create' ? 'Nuevo mensaje urgente' : 'Editar mensaje')

@section('content')
    @php
        $starts = old('starts_at', $urgentMessage->starts_at?->timezone($timezone)->format('Y-m-d\TH:i') ?? '');
        $ends = old('ends_at', $urgentMessage->ends_at?->timezone($timezone)->format('Y-m-d\TH:i') ?? '');
    @endphp

    <form method="POST" action="{{ $mode === 'create' ? route('urgent-messages.store') : route('urgent-messages.update', $urgentMessage) }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm border-start border-danger border-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Título</label>
                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $urgentMessage->title) }}" required placeholder="Ej. Corte de agua potable">
                            @error('title')<div class="invalid-feedback">{{ $errors->first('title') }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Presentación en TV</label>
                            @php
                                $currentLayout = old('layout', $urgentMessage->layout?->value ?? 'text_ticker');
                            @endphp
                            <div class="d-flex flex-column gap-2">
                                @foreach ($layouts as $layoutOption)
                                    <label class="form-check border rounded p-3 mb-0 @error('layout') border-danger @enderror">
                                        <input type="radio" name="layout" value="{{ $layoutOption->value }}" class="form-check-input"
                                            @checked($currentLayout === $layoutOption->value)>
                                        <span class="form-check-label ms-1">
                                            <strong>{{ $layoutOption->label() }}</strong>
                                            @if ($layoutOption === \App\Enums\UrgentMessageLayout::TextTicker)
                                                <span class="d-block small text-muted mt-1">Texto deslizante en la franja inferior mientras sigue la playlist de videos.</span>
                                            @else
                                                <span class="d-block small text-muted mt-1">Ocupa toda la pantalla e interrumpe la reproducción de la playlist.</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('layout')<div class="invalid-feedback d-block">{{ $errors->first('layout') }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="body" class="form-label">Texto del mensaje (cinta)</label>
                            <textarea name="body" id="body" rows="4" class="form-control @error('body') is-invalid @enderror" required
                                placeholder="Ej. INFORMAMOS A LA COMUNIDAD QUE MAÑANA HABRÁ JORNADA DE VACUNACIÓN EN EL CESFAM...">{{ old('body', $urgentMessage->body) }}</textarea>
                            <div class="form-text">Se mostrará en mayúsculas en la cinta. Use frases claras; el texto se repite en bucle.</div>
                            @error('body')<div class="invalid-feedback">{{ $errors->first('body') }}</div>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="starts_at" class="form-label">Inicio ({{ $timezone }})</label>
                                <input type="datetime-local" name="starts_at" id="starts_at" class="form-control" value="{{ $starts }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ends_at" class="form-label">Fin</label>
                                <input type="datetime-local" name="ends_at" id="ends_at" class="form-control" value="{{ $ends }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="priority" class="form-label">Prioridad</label>
                            <input type="number" name="priority" id="priority" class="form-control" min="0" max="999"
                                   value="{{ old('priority', $urgentMessage->priority ?? 10) }}">
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="publish" id="publish" value="1" @checked(old('publish'))>
                            <label class="form-check-label" for="publish">Publicar al guardar (activar en pantallas)</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="applies_to_all_screens" id="all_screens" value="1"
                                @checked(old('applies_to_all_screens', $urgentMessage->applies_to_all_screens))>
                            <label class="form-check-label" for="all_screens"><strong>Todas las pantallas</strong></label>
                        </div>
                    </div>
                    <div class="card-body p-0 screen-list" style="max-height: 360px; overflow-y: auto;">
                        @foreach ($screens as $screen)
                            <label class="list-group-item d-flex gap-2 border-0 border-bottom rounded-0 mb-0">
                                <input type="checkbox" name="screens[]" value="{{ $screen->id }}" class="screen-cb"
                                    @checked(in_array($screen->id, old('screens', $selectedScreens), true))>
                                <span>{{ $screen->name }} <small class="text-muted">{{ $screen->location }}</small></span>
                            </label>
                        @endforeach
                    </div>
                    @error('screens')<div class="text-danger small p-2">{{ $errors->first('screens') }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-danger">Guardar</button>
            <a href="{{ $mode === 'edit' ? route('urgent-messages.show', $urgentMessage) : route('urgent-messages.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        const all = document.getElementById('all_screens');
        const list = document.querySelector('.screen-list');
        function toggle() {
            const off = all.checked;
            list.style.opacity = off ? '0.45' : '1';
            list.querySelectorAll('.screen-cb').forEach(cb => { cb.disabled = off; if (off) cb.checked = false; });
        }
        all?.addEventListener('change', toggle);
        toggle();
    })();
</script>
@endpush
