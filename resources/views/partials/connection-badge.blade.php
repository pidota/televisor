@php
    $labels = [
        'online' => ['ONLINE', 'badge-online'],
        'offline' => ['OFFLINE', 'badge-offline'],
        'pending' => ['PENDIENTE', 'badge-pending'],
        'disabled' => ['DESHABILITADA', 'bg-secondary'],
        'revoked' => ['REVOCADA', 'bg-danger'],
    ];
    [$text, $class] = $labels[$label] ?? ['—', 'bg-secondary'];
@endphp
<span class="badge {{ $class }}">{{ $text }}</span>
