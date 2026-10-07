@php
    $user = auth()->user();
    $isAdmin = $user?->hasRole('admin');
@endphp

<aside class="panel-sidebar d-flex flex-column" id="panelSidebar">
    <div class="panel-sidebar-brand">
        <h1><i class="bi bi-display me-2"></i>{{ config('app.name') }}</h1>
        <small>Cartelería digital municipal</small>
    </div>

    <nav class="panel-nav nav flex-column py-2 flex-grow-1">
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <a href="{{ route('screens.index') }}"
           class="nav-link {{ request()->routeIs('screens.*') && ! request()->routeIs('screen-groups.*') ? 'active' : '' }}">
            <i class="bi bi-tv"></i> Pantallas
        </a>
        <a href="{{ route('screen-groups.index') }}"
           class="nav-link sub {{ request()->routeIs('screen-groups.*') ? 'active' : '' }}">
            <i class="bi bi-collection"></i> Grupos
        </a>

        <div class="panel-nav-section">Contenido</div>
        @php($mediaRouteType = request()->route('mediaAsset')?->type?->value)
        <a href="{{ route('content.videos') }}"
           class="nav-link sub {{ request()->routeIs('content.videos*') || ($mediaRouteType === 'video' && request()->routeIs('media.*')) ? 'active' : '' }}">
            <i class="bi bi-camera-video"></i> Videos
        </a>
        <a href="{{ route('content.images') }}"
           class="nav-link sub {{ request()->routeIs('content.images*') || ($mediaRouteType === 'image' && request()->routeIs('media.*')) ? 'active' : '' }}">
            <i class="bi bi-image"></i> Imágenes
        </a>

        <a href="{{ route('playlists.index') }}"
           class="nav-link {{ request()->routeIs('playlists.*') ? 'active' : '' }}">
            <i class="bi bi-collection-play"></i> Playlists
        </a>

        <a href="{{ route('schedules.index') }}"
           class="nav-link {{ request()->routeIs('schedules.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-event"></i> Programación
        </a>

        <a href="{{ route('urgent-messages.index') }}"
           class="nav-link {{ request()->routeIs('urgent-messages.*') ? 'active' : '' }}">
            <i class="bi bi-exclamation-triangle"></i> Mensajes urgentes
        </a>

        @if ($isAdmin)
            <div class="panel-nav-section">Administración</div>
            <a href="{{ route('users.index') }}"
               class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Usuarios
            </a>
            <a href="{{ route('settings.index') }}"
               class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Configuración
            </a>
        @endif
    </nav>

    <div class="p-3 border-top border-secondary border-opacity-25">
        <div class="small text-white-50 mb-2 px-2">{{ $user?->name }}</div>
        <a href="#" class="nav-link py-2" data-logout-trigger>
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</aside>
