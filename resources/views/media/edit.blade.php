@extends('layouts.app')

@section('title', 'Editar contenido')
@section('page-title', 'Editar contenido')
@section('page-subtitle', $media->name)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('media.update', $media) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $media->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="valid_from" class="form-label">Vigencia desde (opcional)</label>
                                <input type="datetime-local" name="valid_from" id="valid_from" class="form-control @error('valid_from') is-invalid @enderror"
                                       value="{{ old('valid_from', $media->valid_from?->format('Y-m-d\TH:i')) }}">
                                @error('valid_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="valid_until" class="form-label">Vigencia hasta (opcional)</label>
                                <input type="datetime-local" name="valid_until" id="valid_until" class="form-control @error('valid_until') is-invalid @enderror"
                                       value="{{ old('valid_until', $media->valid_until?->format('Y-m-d\TH:i')) }}">
                                @error('valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">Guardar</button>
                            <a href="{{ route('media.show', $media) }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
