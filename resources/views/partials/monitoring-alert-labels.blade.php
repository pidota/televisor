@php
    $map = [
        'offline' => 'Sin conexión reciente',
        'manifest_outdated' => 'Manifiesto desactualizado',
        'storage_low' => 'Poco espacio en dispositivo',
        'never_connected' => 'Nunca se conectó',
    ];
@endphp
@foreach ($alerts as $code)
    <span class="badge text-bg-warning text-dark me-1 mb-1">{{ $map[$code] ?? $code }}</span>
@endforeach
