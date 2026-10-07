<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/panel.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="panel-body">
    <div class="panel-sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="panel-layout d-flex">
        @include('partials.sidebar')

        <div class="panel-main d-flex flex-column">
            @include('partials.topbar')

            <main class="panel-content flex-grow-1">
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            const sidebar = document.getElementById('panelSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const toggle = document.getElementById('sidebarToggle');

            function closeSidebar() {
                sidebar?.classList.remove('show');
                backdrop?.classList.remove('show');
            }

            toggle?.addEventListener('click', function () {
                sidebar?.classList.toggle('show');
                backdrop?.classList.toggle('show');
            });

            backdrop?.addEventListener('click', closeSidebar);

            document.querySelectorAll('[data-logout-trigger]').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    Swal.fire({
                        title: '¿Cerrar sesión?',
                        text: 'Saldrá del panel de administración.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, salir',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#0d3b66',
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            document.getElementById('logout-form')?.submit();
                        }
                    });
                });
            });

            @if (session('success'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });
            @endif

            @if (session('error'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: @json(session('error')),
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
            });
            @endif
        })();
    </script>
    @stack('scripts')
</body>
</html>
