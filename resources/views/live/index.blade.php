@extends('layouts.app')

@section('title', 'Transmisión en vivo')
@section('page-title', 'Transmisión en vivo')
@section('page-subtitle', 'Envía una señal con OBS y muéstrala en las pantallas')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <strong>Datos para OBS Studio</strong>
                </div>
                <div class="card-body">
                    <p class="text-muted">En OBS: Ajustes, Emisión, servicio Personalizado. Deja la clave vacía y no marques autentificación. El usuario y la contraseña ya van dentro del servidor.</p>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Servidor</dt>
                        <dd class="col-sm-8"><code class="user-select-all">{{ $obsServer }}</code></dd>
                        <dt class="col-sm-4">Clave de emisión</dt>
                        <dd class="col-sm-8">Déjala vacía</dd>
                        <dt class="col-sm-4">Dirección del televisor</dt>
                        <dd class="col-sm-8"><code>{{ $hlsUrl }}</code></dd>
                    </dl>
                    <p class="small text-muted mt-3 mb-0">En OBS también puedes agregar una segunda salida hacia Facebook. El televisor no depende de ese enlace.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <form method="POST" action="{{ route('live.update') }}">
                @csrf
                @method('PUT')

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" name="enabled" id="enabled" value="1" @checked(old('enabled', $enabled))>
                            <label class="form-check-label" for="enabled"><strong>Mostrar el vivo en las pantallas</strong></label>
                        </div>
                    </div>
                    <div class="card-body border-bottom">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="applies_to_all_screens" id="all_screens" value="1" @checked(old('applies_to_all_screens', $allScreens))>
                            <label class="form-check-label" for="all_screens">Todas las pantallas activas</label>
                        </div>
                    </div>
                    <div class="card-body p-0 screen-list" style="max-height: 280px; overflow-y: auto;">
                        @forelse ($screens as $screen)
                            <label class="list-group-item d-flex gap-2 border-0 border-bottom rounded-0 mb-0">
                                <input type="checkbox" name="screens[]" value="{{ $screen->id }}" class="screen-cb"
                                    @checked(in_array($screen->id, old('screens', $selectedScreens), true))>
                                <span>{{ $screen->name }} <small class="text-muted">{{ $screen->location }}</small></span>
                            </label>
                        @empty
                            <p class="text-muted p-3 mb-0">No hay pantallas activas.</p>
                        @endforelse
                    </div>
                    @error('screens')<div class="text-danger small p-2">{{ $errors->first('screens') }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const all = document.getElementById('all_screens');
        const list = document.querySelector('.screen-list');
        function toggle() {
            if (!all || !list) return;
            const off = all.checked;
            list.style.opacity = off ? '0.45' : '1';
            list.querySelectorAll('.screen-cb').forEach(function (cb) {
                cb.disabled = off;
            });
        }
        all?.addEventListener('change', toggle);
        toggle();
    })();
</script>
@endpush
