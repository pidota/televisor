@extends('layouts.guest')

@section('title', 'Ingresar')

@section('content')
    <div class="panel-auth-wrapper d-flex align-items-center justify-content-center py-5 px-3">
        <div class="w-100" style="max-width: 420px;">
            <div class="text-center text-white mb-4">
                <div class="display-6"><i class="bi bi-building"></i></div>
                <h1 class="h3 fw-semibold mb-1">Municipalidad</h1>
                <p class="mb-0 opacity-75">Panel de cartelería digital</p>
            </div>

            <div class="card panel-auth-card">
                <div class="card-body p-4 p-md-5">
                    <h2 class="h5 mb-4 text-center">Iniciar sesión</h2>

                    <form method="POST" action="{{ route('login') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   autofocus
                                   autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password"
                                   name="password"
                                   required
                                   autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Recordarme</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2" style="background-color: #0d3b66; border-color: #0d3b66;">
                            Ingresar
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center text-white-50 small mt-4 mb-0">
                Acceso restringido a funcionarios autorizados.
            </p>
        </div>
    </div>
@endsection
