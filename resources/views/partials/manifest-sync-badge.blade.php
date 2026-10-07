@php
    $status = $status ?? 'unknown';
    $class = match ($status) {
        'synced' => 'text-bg-success',
        'outdated' => 'text-bg-warning text-dark',
        default => 'text-bg-light border text-muted',
    };
    $label = match ($status) {
        'synced' => 'Sincronizado',
        'outdated' => 'Pendiente sync',
        default => '—',
    };
@endphp
<span class="badge {{ $class }}">{{ $label }}</span>
