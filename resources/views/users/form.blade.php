@extends('layouts.app')

@section('title', $mode === 'create' ? 'Nuevo usuario' : 'Editar usuario')
@section('page-title', $mode === 'create' ? 'Nuevo usuario' : 'Editar usuario')

@section('content')
    <form method="POST" action="{{ $mode === 'create' ? route('users.store') : route('users.update', $user) }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" required autocomplete="name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required autocomplete="email">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Contraseña
                                @if ($mode === 'edit')
                                    <span class="text-muted fw-normal">(dejar en blanco para no cambiar)</span>
                                @endif
                            </label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                                   autocomplete="new-password" @if ($mode === 'create') required @endif>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                                   autocomplete="new-password" @if ($mode === 'create') required @endif>
                        </div>

                        <div class="mb-0">
                            <label for="role" class="form-label">Rol</label>
                            <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                                @foreach ($roles as $roleOption)
                                    <option value="{{ $roleOption->name }}" @selected(old('role', $currentRole) === $roleOption->name)>
                                        {{ $roleOption->label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                Operador: todo el panel excepto vincular TVs y esta sección de usuarios.
                                Administrador: acceso completo.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">
                        {{ $mode === 'create' ? 'Crear usuario' : 'Guardar cambios' }}
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
@endsection
