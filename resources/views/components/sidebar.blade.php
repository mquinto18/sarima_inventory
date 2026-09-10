<nav class="sidebar">
    <button type="button" class="sidebar-collapse-toggle" id="sidebarCollapseToggle" aria-label="Collapse sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
            <path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
    @include('components.menu')
    <div class="sidebar-logout">
        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
            @csrf
            <button type="submit" title="Logout"
                style="width: 100%; text-align: left; background: none; border: none; display: flex; align-items: center; gap: 12px; padding: 11px 14px; color: #ef4444; font-size: 0.98rem; font-weight: 600; border-radius: 12px; cursor: pointer;">
                <span class="nav-icon" style="background: rgba(239, 68, 68, 0.08);">
                    <!-- Logout Icon -->
                    <svg width="18" height="18" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M16 17l5-5m0 0l-5-5m5 5H9" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M13 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="nav-label">Logout</span>
            </button>
        </form>
    </div>
</nav>

<style>
    .sidebar {
        width: 260px;
        height: calc(100vh - 70px);
        background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        color: #374151;
        position: fixed;
        top: 70px;
        left: 0;

        padding-top: 60px;
        padding-left: 12px;
        padding-right: 12px;
        overflow-y: auto;
        border-right: none;
        box-shadow: 2px 0 24px 0 rgba(99, 102, 241, 0.07);
        z-index: 100;
        transition: width 0.25s ease;
    }

    .sidebar::after {
        content: '';
        position: fixed;
        width: 240px;
        height: 240px;
        left: -100px;
        bottom: -100px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, rgba(99, 102, 241, 0) 70%);
        pointer-events: none;
        z-index: -1;
    }

    .sidebar-collapse-toggle {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 32px;
        height: 32px;
        border-radius: 9px;
        background: rgba(99, 102, 241, 0.12);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 150;
        padding: 0;
        transition: background 0.2s;
    }

    .sidebar-collapse-toggle:hover {
        background: rgba(99, 102, 241, 0.22);
    }

    .sidebar-collapse-toggle svg {
        width: 17px;
        height: 17px;
        color: var(--color-primary, #6366f1);
        transition: transform 0.25s ease;
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar ul li {
        margin-bottom: 8px;
    }

    .sidebar ul li a {
        color: #374151;
        text-decoration: none;
        font-size: 0.98rem;
        padding: 11px 14px;
        display: flex;
        align-items: center;
        border-radius: 12px;
        font-weight: 600;
        gap: 12px;
        white-space: nowrap;
        transition: background 0.2s, color 0.2s, transform 0.15s, box-shadow 0.2s, padding 0.2s;
    }

    .sidebar ul li a:hover {
        transform: translateX(2px);
        background: var(--color-primary-soft, #e0e7ff);
        color: var(--color-primary-dark, #4338ca);
    }

    .nav-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: rgba(99, 102, 241, 0.08);
        transition: background 0.2s;
    }

    .nav-icon svg {
        stroke: var(--color-primary, #6366f1) !important;
        fill: none !important;
        width: 18px;
        height: 18px;
        transition: stroke 0.2s;
        opacity: 0.9;
    }

    .sidebar ul li a:hover .nav-icon {
        background: rgba(99, 102, 241, 0.15);
    }

    .sidebar ul li a.active {
        background: linear-gradient(90deg, var(--color-primary, #6366f1) 0%, var(--color-primary-dark, #4338ca) 100%);
        color: #fff;
        box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.35);
    }

    .sidebar ul li a.active .nav-icon {
        background: rgba(255, 255, 255, 0.2);
    }

    .sidebar ul li a.active .nav-icon svg {
        stroke: #fff !important;
        opacity: 1;
    }

    .sidebar-logout {
        position: absolute;
        bottom: 20px;
        left: 12px;
        right: 12px;
    }

    .sidebar-logout button:hover {
        background: #fee2e2;
    }

    /* ---- Collapsed state (icon-only) ---- */
    body.sidebar-collapsed .sidebar {
        width: 84px;
    }

    body.sidebar-collapsed .nav-label {
        display: none;
    }

    body.sidebar-collapsed .sidebar ul li a,
    body.sidebar-collapsed .sidebar-logout button {
        justify-content: center;
        padding: 11px;
        gap: 0;
    }

    body.sidebar-collapsed .sidebar-collapse-toggle svg {
        transform: rotate(180deg);
    }

    @media (max-width: 900px) {
        .sidebar-collapse-toggle {
            display: none;
        }

        body.sidebar-collapsed .sidebar {
            width: 260px;
        }

        body.sidebar-collapsed .nav-label {
            display: inline;
        }

        body.sidebar-collapsed .sidebar ul li a,
        body.sidebar-collapsed .sidebar-logout button {
            justify-content: flex-start;
            padding: 11px 14px;
            gap: 12px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('sidebarCollapseToggle');
        if (!toggle) return;

        toggle.addEventListener('click', function () {
            var collapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', collapsed ? 'true' : 'false');
            toggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        });
    });
</script>
