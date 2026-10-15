@extends('layouts.app')

@section('title', 'Usuarios')
@section('page-title', 'Usuarios')
@section('page-subtitle', 'Cuentas del panel municipal')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between mb-4">
        @can('create', \App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm" style="background-color: #0d3b66; border-color: #0d3b66;">
                <i class="bi bi-person-plus me-1"></i> Nuevo usuario
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $panelUser)
                        @php($role = $panelUser->roles->first())
                        <tr>
                            <td>
                                <strong>{{ $panelUser->name }}</strong>
                                @if (auth()->id() === $panelUser->id)
                                    <span class="badge text-bg-secondary ms-1">Usted</span>
                                @endif
                            </td>
                            <td>{{ $panelUser->email }}</td>
                            <td>
                                @if ($role)
                                    <span class="badge {{ $role->name === 'admin' ? 'text-bg-primary' : 'text-bg-info' }}">
                                        {{ $role->label }}
                                    </span>
                                @else
                                    <span class="text-muted">Sin rol</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('update', $panelUser)
                                    <a href="{{ route('users.edit', $panelUser) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                @endcan
                                @can('delete', $panelUser)
                                    <form method="POST" action="{{ route('users.destroy', $panelUser) }}" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar este usuario?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">No hay usuarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-white">{{ $users->links() }}</div>
        @endif
    </div>

    <p class="small text-muted mt-3 mb-0">
        Los usuarios con rol <strong>Operador</strong> pueden gestionar contenido, playlists, programación y mensajes urgentes,
        pero <strong>solo un administrador</strong> puede vincular nuevos televisores.
    </p>
@endsection
