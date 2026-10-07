@extends('layouts.app')

@section('title', 'Nuevo grupo')
@section('page-title', 'Nuevo grupo de pantallas')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('screen-groups.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea name="description" id="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">Crear</button>
                            <a href="{{ route('screen-groups.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
