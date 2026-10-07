@extends('layouts.app')

@section('title', 'Completar vinculación')
@section('page-title', 'Completar vinculación')
@section('page-subtitle')
    Código {{ $pairing->code }} · expira {{ $pairing->expires_at->locale('es')->diffForHumans() }}
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('screens.pair.store', $pairing) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre de pantalla <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="Ej. Hall Municipalidad" required autofocus>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Ubicación</label>
                            <input type="text" name="location" id="location" class="form-control @error('location') is-invalid @enderror"
                                   value="{{ old('location') }}" placeholder="Ej. Edificio consistorial, 1° piso">
                            @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Descripción (opcional)</label>
                            <textarea name="description" id="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">
                                Confirmar vinculación
                            </button>
                            <a href="{{ route('screens.pair.create') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
