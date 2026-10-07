@extends('layouts.app')

@section('title', 'Editar playlist')
@section('page-title', 'Editar playlist')
@section('page-subtitle', $playlist->name)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('playlists.update', $playlist) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $playlist->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea name="description" id="description" rows="2" class="form-control">{{ old('description', $playlist->description) }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label for="status" class="form-label">Estado</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                @foreach (['draft' => 'Borrador', 'active' => 'Activa', 'archived' => 'Archivada'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $playlist->status->value) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">Guardar</button>
                            <a href="{{ route('playlists.show', $playlist) }}" class="btn btn-outline-secondary">Volver</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
