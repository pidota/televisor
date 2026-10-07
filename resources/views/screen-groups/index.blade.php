@extends('layouts.app')

@section('title', 'Grupos de pantallas')
@section('page-title', 'Grupos de pantallas')
@section('page-subtitle', 'Asignación masiva de contenido')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        @can('create', \App\Models\ScreenGroup::class)
            <a href="{{ route('screen-groups.create') }}" class="btn btn-primary btn-sm" style="background-color: #0d3b66; border-color: #0d3b66;">
                <i class="bi bi-plus-lg"></i> Nuevo grupo
            </a>
        @else
            <span></span>
        @endcan
        <a href="{{ route('screens.index') }}" class="btn btn-outline-secondary btn-sm">Pantallas</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Pantallas</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr>
                            <td><strong>{{ $group->name }}</strong></td>
                            <td>{{ $group->screens_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('screen-groups.show', $group) }}" class="btn btn-sm btn-outline-primary">Administrar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">Sin grupos definidos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($groups->hasPages())
            <div class="card-footer bg-white">{{ $groups->links() }}</div>
        @endif
    </div>
@endsection
