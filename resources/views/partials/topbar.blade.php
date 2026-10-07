<div class="panel-topbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-secondary d-lg-none" id="sidebarToggle" aria-label="Menú">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <h2 class="h5 mb-0">@yield('page-title', 'Panel')</h2>
            @hasSection('page-subtitle')
                <p class="text-muted small mb-0">@yield('page-subtitle')</p>
            @endif
        </div>
    </div>
    <div class="text-muted small d-none d-md-block">
        <i class="bi bi-person-circle me-1"></i>{{ auth()->user()?->email }}
    </div>
</div>
