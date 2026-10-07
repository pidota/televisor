@extends('layouts.app')

@section('title', 'Nueva playlist')
@section('page-title', 'Nueva playlist')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('playlists.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required autofocus placeholder="Ej. Institucional">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea name="description" id="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label for="status" class="form-label">Estado inicial</label>
                            <select name="status" id="status" class="form-select">
                                <option value="draft" @selected(old('status', 'draft') === 'draft')>Borrador</option>
                                <option value="active" @selected(old('status') === 'active')>Activa</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">Crear</button>
                            <a href="{{ route('playlists.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
