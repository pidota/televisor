@extends('layouts.app')

@section('title', 'Vincular pantalla')
@section('page-title', 'Vincular pantalla')
@section('page-subtitle', 'Ingrese el código mostrado en el televisor')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <p class="text-muted small">
                        En el televisor debe aparecer un código de <strong>6 dígitos</strong>.
                        Solicítelo iniciando la app (llamada <code>POST /api/v1/device/pair</code> en pruebas).
                    </p>

                    <form method="POST" action="{{ route('screens.pair.verify') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="code" class="form-label">Código de vinculación</label>
                            <input type="text"
                                   name="code"
                                   id="code"
                                   class="form-control form-control-lg text-center letter-spacing @error('code') is-invalid @enderror"
                                   maxlength="6"
                                   pattern="\d{6}"
                                   inputmode="numeric"
                                   placeholder="000000"
                                   value="{{ old('code') }}"
                                   required
                                   autofocus>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2" style="background-color: #0d3b66; border-color: #0d3b66;">
                            Continuar
                        </button>
                    </form>
                </div>
            </div>
            <p class="small text-muted text-center mb-0">
                El código expira en {{ app(\App\Support\SettingStore::class)->getInt('pairing.code_ttl_minutes', 15) }} minutos y solo puede usarse una vez.
            </p>
        </div>
    </div>
@endsection

@push('styles')
<style>
    #code { letter-spacing: 0.35em; font-weight: 600; }
</style>
@endpush
