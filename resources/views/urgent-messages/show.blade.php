@extends('layouts.app')

@section('title', $message->title)
@section('page-title', $message->title)
@section('page-subtitle', 'Mensaje urgente')

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('urgent-messages.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Listado</a>
        @can('update', $message)
            <a href="{{ route('urgent-messages.edit', $message) }}" class="btn btn-outline-primary btn-sm">Editar</a>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm border-start border-danger border-4">
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;">{{ $message->body }}</p>
                </div>
            </div>
            <dl class="row small mt-3 text-muted">
                <dt class="col-sm-3">Vigencia</dt>
                <dd class="col-sm-9">
                    {{ $message->starts_at->timezone($timezone)->format('d/m/Y H:i') }}
                    —
                    {{ $message->ends_at->timezone($timezone)->format('d/m/Y H:i') }}
                </dd>
                <dt class="col-sm-3">Alcance</dt>
                <dd class="col-sm-9">
                    @if ($message->applies_to_all_screens)
                        Todas las pantallas activas ({{ $affectedCount }})
                    @else
                        {{ $affectedCount }} pantalla(s) seleccionada(s)
                    @endif
                </dd>
                <dt class="col-sm-3">Presentación</dt>
                <dd class="col-sm-9">{{ $message->layout->label() }}</dd>
                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9 text-capitalize">{{ $message->status->value }}</dd>
            </dl>
        </div>
        <div class="col-lg-4">
            @can('update', $message)
                <div class="d-grid gap-2">
                    @if ($message->status === \App\Enums\UrgentMessageStatus::Draft)
                        <form method="POST" action="{{ route('urgent-messages.activate', $message) }}">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100">Activar ahora</button>
                        </form>
                    @endif
                    @if ($message->status === \App\Enums\UrgentMessageStatus::Active)
                        <form method="POST" action="{{ route('urgent-messages.cancel', $message) }}">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">Cancelar mensaje</button>
                        </form>
                    @endif
                </div>
            @endcan
            @can('delete', $message)
                <form method="POST" action="{{ route('urgent-messages.destroy', $message) }}" class="mt-3" data-confirm="¿Eliminar mensaje?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Eliminar</button>
                </form>
            @endcan

            @unless ($message->applies_to_all_screens)
                <ul class="list-group list-group-flush small mt-3 shadow-sm rounded">
                    @foreach ($message->targets as $target)
                        <li class="list-group-item">{{ $target->screen?->name ?? 'Pantalla #'.$target->screen_id }}</li>
                    @endforeach
                </ul>
            @endunless
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({ title: 'Confirmar', text: form.getAttribute('data-confirm'), icon: 'warning', showCancelButton: true, confirmButtonColor: '#0d3b66' })
                .then(r => { if (r.isConfirmed) form.submit(); });
        });
    });
</script>
@endpush
