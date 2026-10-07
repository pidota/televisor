@extends('layouts.app')

@section('title', $screen->name ?? 'Pantalla')
@section('page-title', $screen->name ?? 'Pantalla sin nombre')
@section('page-subtitle', $screen->location)

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('screens.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Listado
        </a>
        @can('update', $screen)
            <a href="{{ route('screens.edit', $screen) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil"></i> Editar
            </a>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h3 class="h6 text-muted mb-0">Estado operativo</h3>
                        @include('partials.connection-badge', ['label' => $screen->connection_label])
                    </div>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Estado registro</dt>
                        <dd class="col-sm-8 text-capitalize">{{ $screen->status->value }}</dd>

                        <dt class="col-sm-4">Reproduciendo (último reporte)</dt>
                        <dd class="col-sm-8">{{ $screen->currentPlaylist?->name ?? $screen->currentMediaAsset?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Contenido efectivo ahora</dt>
                        <dd class="col-sm-8">
                            @if ($content['source'] === 'urgent' && ($content['urgent_message'] ?? null))
                                <a href="{{ route('urgent-messages.show', $content['urgent_message']) }}">{{ $content['urgent_message']->title }}</a>
                            @elseif ($content['playlist'])
                                <a href="{{ route('playlists.show', $content['playlist']) }}">{{ $content['playlist']->name }}</a>
                                @if ($content['urgent_message'] ?? null)
                                    <span class="badge text-bg-danger ms-1">Cinta: {{ $content['urgent_message']->title }}</span>
                                @endif
                                <span class="badge text-bg-light border ms-1">
                                    @if ($content['source'] === 'schedule')
                                        Programación{{ $content['schedule'] ? ': '.$content['schedule']->name : '' }}
                                    @elseif ($content['source'] === 'assignment')
                                        Asignación
                                    @else
                                        —
                                    @endif
                                </span>
                            @else
                                <span class="text-muted">Sin playlist resuelta</span>
                            @endif
                        </dd>

                        @if ($screen->groups->isNotEmpty())
                            <dt class="col-sm-4">Grupos</dt>
                            <dd class="col-sm-8">{{ $screen->groups->pluck('name')->join(', ') }}</dd>
                        @endif

                        <dt class="col-sm-4">Última conexión</dt>
                        <dd class="col-sm-8">
                            @if ($screen->last_seen_at)
                                {{ $screen->last_seen_at->locale('es')->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                ({{ $screen->last_seen_at->locale('es')->diffForHumans() }})
                            @else
                                Sin registros
                            @endif
                        </dd>

                        <dt class="col-sm-4">IP</dt>
                        <dd class="col-sm-8">{{ $screen->last_ip ?? '—' }}</dd>

                        @if ($screen->description)
                            <dt class="col-sm-4">Descripción</dt>
                            <dd class="col-sm-8">{{ $screen->description }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <strong class="small text-muted text-uppercase">Dispositivo</strong>
                </div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">UUID</dt>
                        <dd class="col-sm-8"><code>{{ $screen->uuid }}</code></dd>
                        <dt class="col-sm-4">Modelo</dt>
                        <dd class="col-sm-8">{{ $screen->device_model ?? '—' }}</dd>
                        <dt class="col-sm-4">Android</dt>
                        <dd class="col-sm-8">{{ $screen->android_version ?? '—' }}</dd>
                        <dt class="col-sm-4">App</dt>
                        <dd class="col-sm-8">{{ $screen->app_version ?? '—' }}</dd>
                        <dt class="col-sm-4">Resolución</dt>
                        <dd class="col-sm-8">{{ $screen->resolution ?? '—' }}</dd>
                        <dt class="col-sm-4">Almacenamiento libre</dt>
                        <dd class="col-sm-8">
                            @if ($screen->storage_free_bytes)
                                {{ number_format($screen->storage_free_bytes / 1024 / 1024 / 1024, 2, ',', '.') }} GB
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="col-sm-4">Manifiesto (servidor)</dt>
                        <dd class="col-sm-8">v{{ $screen->manifest_version }}</dd>
                        @php($mon = $screen->monitoring ?? [])
                        <dt class="col-sm-4">Sync manifiesto</dt>
                        <dd class="col-sm-8">
                            @include('partials.manifest-sync-badge', ['status' => $mon['manifest_status'] ?? 'unknown'])
                            <span class="ms-2 small text-muted">
                                Reportado: v{{ $mon['reported_manifest_version'] ?? '?' }}
                            </span>
                        </dd>
                        @if ($mon['has_alerts'] ?? false)
                            <dt class="col-sm-4">Alertas</dt>
                            <dd class="col-sm-8">@include('partials.monitoring-alert-labels', ['alerts' => $mon['alerts']])</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong class="small text-muted text-uppercase">Historial de heartbeats</strong>
                    <span class="badge text-bg-light border">Últimos {{ $heartbeats->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Manifiesto</th>
                                <th>Playlist</th>
                                <th>Media</th>
                                <th>Disco libre</th>
                                <th>App</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($heartbeats as $hb)
                                <tr>
                                    <td class="small">{{ $hb->created_at?->locale('es')->format('d/m H:i:s') }}</td>
                                    <td>v{{ $hb->manifest_version }}</td>
                                    <td class="small">{{ $hb->playlist?->name ?? '—' }}</td>
                                    <td class="small">{{ $hb->mediaAsset?->name ?? '—' }}</td>
                                    <td class="small">
                                        @if ($hb->storage_free_bytes)
                                            {{ number_format($hb->storage_free_bytes / 1024 / 1024 / 1024, 1, ',', '.') }} GB
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="small">{{ $hb->app_version ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">Sin heartbeats aún</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('disable', $screen)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h3 class="h6 mb-3">Acciones</h3>
                        <div class="d-grid gap-2">
                            @if ($screen->status === \App\Enums\ScreenStatus::Disabled)
                                <form method="POST" action="{{ route('screens.enable', $screen) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm w-100">Habilitar pantalla</button>
                                </form>
                            @elseif ($screen->status !== \App\Enums\ScreenStatus::Revoked)
                                <form method="POST" action="{{ route('screens.disable', $screen) }}" data-confirm="¿Deshabilitar esta pantalla?">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm w-100">Deshabilitar</button>
                                </form>
                            @endif

                            @can('revoke', $screen)
                                @if ($screen->status !== \App\Enums\ScreenStatus::Revoked)
                                    <form method="POST" action="{{ route('screens.revoke', $screen) }}" data-confirm="¿Revocar token? El televisor deberá vincularse de nuevo.">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">Revocar acceso</button>
                                    </form>
                                @endif
                            @endcan

                            @can('delete', $screen)
                                <form method="POST" action="{{ route('screens.destroy', $screen) }}" data-confirm="¿Eliminar pantalla del sistema?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm w-100">Eliminar</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @endcan

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <strong class="small text-muted text-uppercase">Vinculación reciente</strong>
                </div>
                <ul class="list-group list-group-flush small">
                    @forelse ($screen->pairingCodes as $code)
                        <li class="list-group-item">
                            Código {{ $code->code }}
                            @if ($code->used_at)
                                <span class="text-success">· usado</span>
                            @elseif ($code->isExpired())
                                <span class="text-muted">· expirado</span>
                            @else
                                <span class="text-warning">· vigente</span>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Sin códigos registrados</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Confirmar',
                text: form.getAttribute('data-confirm'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d3b66',
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
</script>
@endpush
