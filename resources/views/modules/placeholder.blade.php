@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body py-5 text-center">
            <i class="bi bi-tools display-5 text-muted d-block mb-3"></i>
            <h3 class="h5">Módulo en desarrollo</h3>
            <p class="text-muted mb-4">
                La sección <strong>{{ $title }}</strong> se implementará en una etapa posterior del proyecto.
            </p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary" style="background-color: #0d3b66; border-color: #0d3b66;">
                <i class="bi bi-arrow-left me-1"></i> Volver al dashboard
            </a>
        </div>
    </div>
@endsection
