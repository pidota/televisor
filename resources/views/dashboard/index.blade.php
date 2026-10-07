@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Monitoreo')
@section('page-subtitle', 'Estado en tiempo real de pantallas y dispositivos')

@section('content')
    @php
        $storageMb = $summary['storage_used_bytes'] > 0
            ? round($summary['storage_used_bytes'] / 1024 / 1024, 1)
            : 0;
        $filter = $filters['filter'] ?? 'all';
    @endphp

    @if ($autoRefresh)
        <div class="alert alert-light border small py-2 mb-3 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-arrow-repeat me-1"></i> Actualización automática cada 60 s</span>
            <a href="{{ route('dashboard', ['filter' => $filter, 'refresh' => '0']) }}" class="small">Desactivar</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Pantallas activas</div>
                            <div class="stat-value">{{ $summary['screens_active'] }}</div>
                            <div class="small text-muted">Total registradas: {{ $summary['screens_total'] }}</div>
                        </div>
                        <span class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-tv"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Online / Offline</div>
                            <div class="stat-value">
                                <span class="text-success">{{ $summary['screens_online'] }}</span>
                                <span class="text-muted fs-5">/</span>
                                <span class="text-secondary">{{ $summary['screens_offline'] }}</span>
                            </div>
                        </div>
                        <span class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-broadcast"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card border-warning border-opacity-25">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Con alertas</div>
                            <div class="stat-value text-warning">{{ $summary['screens_with_alerts'] }}</div>
                            <div class="small text-muted">
                                Manifiesto: {{ $summary['alerts_manifest_outdated'] }} ·
                                Disco: {{ $summary['alerts_storage_low'] }}
                            </div>
                        </div>
                        <span class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Contenido / urgentes</div>
                            <div class="stat-value">{{ $summary['playlists_active'] }}</div>
                            <div class="small {{ $summary['urgent_messages_active'] > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                Urgentes: {{ $summary['urgent_messages_active'] }}
                                · Prog.: {{ $summary['schedules_active'] }}
                            </div>
                            <div class="small text-muted">Biblioteca: {{ number_format($storageMb, 1, ',', '.') }} MB</div>
                        </div>
                        <span class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-collection-play"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (! empty($alertFeed))
        <div class="card border-warning border-opacity-50 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong class="small text-uppercase text-muted">Atención requerida</strong>
                <a href="{{ route('dashboard', ['filter' => 'alerts']) }}" class="btn btn-sm btn-outline-warning">Ver solo alertas</a>
            </div>
            <ul class="list-group list-group-flush">
                @foreach (array_slice($alertFeed, 0, 8) as $item)
                    <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <a href="{{ route('screens.show', $item['screen']) }}" class="fw-semibold text-decoration-none">
                            {{ $item['screen']->name ?? 'Sin nombre' }}
                        </a>
                        <div>@include('partials.monitoring-alert-labels', ['alerts' => $item['alerts']])</div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <div class="btn-group btn-group-sm" role="group">
            @foreach (['all' => 'Todas', 'online' => 'Online', 'offline' => 'Offline', 'alerts' => 'Con alertas'] as $key => $label)
                <a href="{{ route('dashboard', ['filter' => $key === 'all' ? null : $key, 'refresh' => $autoRefresh ? '1' : '0']) }}"
                   class="btn {{ $filter === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>
        <span class="badge text-bg-light border">{{ $screens->count() }} pantalla(s)</span>
    </div>

    @if ($screens->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-tv display-6 d-block mb-3"></i>
                <p class="mb-0">No hay pantallas para el filtro seleccionado.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Pantalla</th>
                            <th>Estado</th>
                            <th>Último visto</th>
                            <th>Reproducción reportada</th>
                            <th>Manifiesto</th>
                            <th>App / dispositivo</th>
                            <th>Almacenamiento</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($screens as $screen)
                            @php
                                $mon = $screen->monitoring ?? [];
                                $playing = $screen->currentMediaAsset?->name
                                    ?? $screen->currentPlaylist?->name
                                    ?? '—';
                            @endphp
                            <tr class="{{ ($mon['has_alerts'] ?? false) ? 'table-warning table-warning-subtle' : '' }}">
                                <td>
                                    <a href="{{ route('screens.show', $screen) }}" class="fw-semibold text-decoration-none">
                                        {{ $screen->name ?? 'Sin nombre' }}
                                    </a>
                                    @if ($screen->location)
                                        <div class="small text-muted">{{ $screen->location }}</div>
                                    @endif
                                </td>
                                <td>
                                    @include('partials.connection-badge', ['label' => $mon['connection'] ?? 'offline'])
                                    @if ($mon['has_alerts'] ?? false)
                                        <div class="mt-1">@include('partials.monitoring-alert-labels', ['alerts' => $mon['alerts']])</div>
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($screen->last_seen_at)
                                        {{ $screen->last_seen_at->locale('es')->diffForHumans() }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small">{{ $playing }}</td>
                                <td class="small">
                                    @include('partials.manifest-sync-badge', ['status' => $mon['manifest_status'] ?? 'unknown'])
                                    <div class="text-muted mt-1">
                                        v{{ $mon['reported_manifest_version'] ?? '?' }}
                                        / {{ $mon['expected_manifest_version'] ?? $screen->manifest_version }}
                                    </div>
                                </td>
                                <td class="small">
                                    {{ $screen->app_version ?? '—' }}
                                    @if ($screen->device_model)
                                        <div class="text-muted">{{ $screen->device_model }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    @if (($mon['storage_free_percent'] ?? null) !== null)
                                        {{ $mon['storage_free_percent'] }}% libre
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('screens.show', $screen) }}" class="btn btn-sm btn-outline-primary">Detalle</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@if ($autoRefresh)
    @push('scripts')
        <script>
            setTimeout(function () { window.location.reload(); }, 60000);
        </script>
    @endpush
@endif
