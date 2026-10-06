<header class="top-header-modern">

    <style>
        .top-header-modern {
            width: 100%;
            background: #ffffff;
            box-shadow: 0 2px 16px 0 rgba(31, 41, 55, 0.06);
            border-bottom: 3px solid transparent;
            border-image: linear-gradient(90deg, #6366f1, #818cf8, #6366f1) 1;
            padding: 0;
            min-height: 70px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 2000;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 48px;
            min-height: 70px;
        }

        .header-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .main-title {
            font-size: 1.7rem;
            font-weight: 900;
            background: linear-gradient(90deg, #4338ca 0%, #6366f1 60%, #818cf8 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            margin-bottom: 0.1em;
        }

        .subtitle {
            color: #374151;
            font-size: 1.08rem;
            font-weight: 500;
            margin-bottom: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .notification-wrapper {
            position: relative;
            z-index: 9999;
        }

        .notification-bell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 12px;
            border-radius: 50%;
            background: transparent;
            transition: background 0.2s, transform 0.15s;
            width: 50px;
            height: 50px;
            box-sizing: border-box;
            pointer-events: auto !important;
            z-index: 10002;
        }

        .notification-bell:hover {
            background: var(--color-primary-soft, #e0e7ff);
            transform: translateY(-1px);
        }

        .notification-bell svg {
            stroke: #6366f1;
            width: 26px;
            height: 26px;
            transition: stroke 0.2s;
            pointer-events: none; /* Let clicks pass through to parent */
        }

        .notification-bell span {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            border: 2px solid #fff;
            z-index: 1002;
            pointer-events: none; /* Let clicks pass through to parent */
        }

        .notification-dropdown {
            display: block;
            position: absolute;
            right: 0;
            top: 100%;
            background: #fff;
            border: 1px solid #f1f1f4;
            border-radius: var(--radius-md, 12px);
            box-shadow: var(--shadow-lg, 0 8px 32px rgba(99, 102, 241, 0.16));
            min-width: 320px;
            max-width: 400px;
            z-index: 99999;
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-6px) scale(0.98);
            transform-origin: top right;
            transition: opacity var(--dur-base, 180ms) var(--ease-out, ease),
                transform var(--dur-base, 180ms) var(--ease-out, ease),
                visibility 0s linear var(--dur-base, 180ms);
        }

        .notification-dropdown.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: none;
            transition: opacity var(--dur-base, 180ms) var(--ease-out, ease),
                transform var(--dur-base, 180ms) var(--ease-out, ease),
                visibility 0s linear 0s;
        }

        /* Rows used inline onmouseover handlers, which cannot be transitioned.
           Same colour, now animatable. */
        .notification-row {
            transition: background-color var(--dur-fast, 120ms) var(--ease-out, ease);
        }

        .notification-row:hover {
            background-color: #f9f9f9;
        }

        /* Notification rows double as shortcuts to the thing they describe. */
        .notification-row--link {
            display: block;
            padding: 12px 16px;
            border-bottom: 1px solid #f5f5f5;
            color: inherit;
            text-decoration: none;
        }

        .notification-row--link:hover,
        .notification-row--link:focus-visible {
            text-decoration: none;
            color: inherit;
            outline: none;
            background-color: var(--color-primary-soft, #e0e7ff);
        }

        .notification-row__chevron {
            color: #9ca3af;
            flex-shrink: 0;
            transition: transform var(--dur-fast, 120ms) var(--ease-out, ease),
                color var(--dur-fast, 120ms) var(--ease-out, ease);
        }

        .notification-row--link:hover .notification-row__chevron {
            color: var(--color-primary, #6366f1);
            transform: translateX(2px);
        }

        .notification-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 11px 16px;
            border-top: 1px solid #eee;
            background: #fbfbfd;
            color: var(--color-primary, #6366f1);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 0 0 var(--radius-md, 12px) var(--radius-md, 12px);
            transition: background-color var(--dur-fast, 120ms) var(--ease-out, ease);
        }

        .notification-footer:hover,
        .notification-footer:focus-visible {
            background: var(--color-primary-soft, #e0e7ff);
            color: var(--color-primary-dark, #4338ca);
            text-decoration: none;
            outline: none;
        }

        .user-wrapper {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 14px;
            background: #fff;
            border-radius: 50px;
            padding: 6px 18px 6px 10px;
            box-shadow: 0 2px 8px 0 rgba(99, 102, 241, 0.06);
            transition: box-shadow 0.18s, transform 0.15s;
        }

        .user-wrapper:hover {
            box-shadow: 0 4px 16px 0 rgba(99, 102, 241, 0.13);
            transform: translateY(-1px);
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1 0%, #60a5fa 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 700;
            margin-right: 6px;
            box-shadow: 0 2px 8px 0 rgba(99, 102, 241, 0.10);
        }

        .user-name {
            font-weight: 700;
            color: #23272f;
            font-size: 1.08rem;
        }

        .user-role {
            color: #6366f1;
            font-size: 0.98rem;
            font-weight: 500;
        }

        .dropdown-menu {
            display: block;
            position: absolute;
            /* Bootstrap 4.6 (CDN, layouts/app.blade.php) also styles
               .dropdown-menu and sets `left: 0`. With both left and right set the
               box is over-constrained and `left` wins in LTR, so `right: 0` was
               ignored and the menu grew rightward off the viewport on narrower
               windows. Resetting left restores right-edge anchoring. */
            left: auto;
            right: 0;
            top: 100%;
            background: #fff;
            border: 1px solid #f1f1f4;
            box-shadow: var(--shadow-lg, 0 8px 32px rgba(99, 102, 241, 0.16));
            min-width: 200px;
            z-index: 1000;
            border-radius: var(--radius-md, 12px);
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-6px) scale(0.98);
            transform-origin: top right;
            transition: opacity var(--dur-base, 180ms) var(--ease-out, ease),
                transform var(--dur-base, 180ms) var(--ease-out, ease),
                visibility 0s linear var(--dur-base, 180ms);
        }

        .dropdown-menu.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: none;
            transition: opacity var(--dur-base, 180ms) var(--ease-out, ease),
                transform var(--dur-base, 180ms) var(--ease-out, ease),
                visibility 0s linear 0s;
        }

        .dropdown-menu-item {
            transition: background-color var(--dur-fast, 120ms) var(--ease-out, ease);
        }

        .dropdown-menu-item:hover {
            background-color: #f9f9f9;
        }
    </style>
    <div class="header-content">
        <div style="display:flex; align-items:center;">
            <button type="button" class="hamburger-btn" id="sidebarToggle" aria-label="Toggle menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div class="header-brand">
                <div class="brand-mark">
                    <img src="{{ asset('images/logo-icon-64.png') }}" alt="Larios Pharmacy">
                </div>
                <div class="header-text">
                    <div class="main-title">LARIOS PHARMACY</div>
                    <div class="subtitle">Sales Forecasting &amp; Inventory Management</div>
                </div>
            </div>
        </div>
        <div class="header-actions">
            <div class="notification-wrapper">
                <div class="notification-bell">
                    <svg width="26" height="26" fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24" style="pointer-events: none;">
                        <path
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11c0-3.07-1.64-5.64-5-5.958V4a1 1 0 1 0-2 0v1.042C6.64 5.36 5 7.929 5 11v3.159c0 .538-.214 1.055-.595 1.436L3 17h5m7 0v1a3 3 0 1 1-6 0v-1m6 0H9"
                            stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;" />
                    </svg>
                    @if(isset($notificationCount) && $notificationCount > 0)
                        <span style="pointer-events: none;">{{ $notificationCount > 9 ? '9+' : $notificationCount }}</span>
                    @endif
                </div>
                <!-- Notification Dropdown -->
                <div class="notification-dropdown" id="notificationDropdown">
                    <div style="padding: 12px 16px; border-bottom: 1px solid #eee; font-weight: 600; color: #23272f;">
                        Reorder Notifications
                        @if(isset($reorderCount) && $reorderCount > 0)
                            <span
                                style="float: right; background: #ef4444; color: white; border-radius: 12px; padding: 2px 8px; font-size: 12px;">{{ $reorderCount }}</span>
                        @endif
                    </div>
                    <div style="max-height: 300px; overflow-y: auto;">
                        @if(isset($reorderNotifications) && count($reorderNotifications) > 0)
                            @foreach($reorderNotifications as $notification)
                                {{-- Each notification is a shortcut: it opens Inventory
                                     already searched down to this product. --}}
                                <a href="{{ url('/inventory') }}?search={{ urlencode($notification['name']) }}"
                                    class="notification-row notification-row--link"
                                    title="Open {{ $notification['name'] }} in Inventory">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        @if($notification['priority'] === 'High')
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444; flex-shrink: 0;"></div>
                                        @elseif($notification['priority'] === 'Medium')
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; flex-shrink: 0;"></div>
                                        @else
                                            <div style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; flex-shrink: 0;"></div>
                                        @endif
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="font-weight: 500; color: #23272f; font-size: 14px;">
                                                {{ $notification['name'] }}</div>
                                            <div style="color: #666; font-size: 12px;">
                                                Stock: {{ $notification['current_stock'] }} | Need:
                                                {{ $notification['recommended_quantity'] }} units
                                            </div>
                                        </div>
                                        <div style="text-align: right; flex-shrink: 0;">
                                            <div style="font-size: 11px; color: #ef4444; font-weight: 600;">
                                                {{ $notification['priority'] }}</div>
                                        </div>
                                        <svg class="notification-row__chevron" width="16" height="16" fill="none"
                                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        @endif
                        @if(isset($pendingApprovalCount) && $pendingApprovalCount > 0)
                            {{-- Whole row is the shortcut now, not just the "View" word. --}}
                            <a href="/new-approval-requests" class="notification-row notification-row--link"
                                style="background: #f8fafc;" title="Open Approval Requests">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 8px; height: 8px; border-radius: 50%; background: #6366f1; flex-shrink: 0;"></div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 500; color: #23272f; font-size: 14px;">Pending Approval
                                            Requests</div>
                                        <div style="color: #666; font-size: 12px;">{{ $pendingApprovalCount }} request(s)
                                            need admin review</div>
                                    </div>
                                    <svg class="notification-row__chevron" width="16" height="16" fill="none"
                                        stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </a>
                        @endif
                        @if((!isset($reorderNotifications) || count($reorderNotifications) === 0) && (!isset($pendingApprovalCount) || $pendingApprovalCount === 0))
                            <div style="padding: 20px 16px; text-align: center; color: #666;">
                                <div style="font-size: 24px; margin-bottom: 8px;">✅</div>
                                <div style="font-weight: 500;">All products are well-stocked!</div>
                                <div style="font-size: 12px; margin-top: 4px; color: #999;">No notifications at this time
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Footer shortcut. Staff cannot open the reorder
                         recommendations panel, so they get the plain list. --}}
                    @if(isset($reorderNotifications) && count($reorderNotifications) > 0)
                        <a class="notification-footer"
                            href="{{ url('/inventory') }}{{ Auth::user()->role !== 'staff' ? '?reorder=1' : '' }}">
                            <span>View all reorder recommendations</span>
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
            <div class="user-wrapper">
                <span class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                <div class="user-info">
                    <span class="user-name">{{ Auth::user()->name }}</span><br>
                    <span class="user-role">{{ ucfirst(Auth::user()->role) }}</span>
                </div>
                <div class="dropdown-menu" id="userDropdown">
                    <div style="padding: 12px 16px; border-bottom: 1px solid #eee;">
                        <div style="font-weight: 600; color: #23272f; font-size: 14px;">{{ Auth::user()->name }}</div>
                        <div style="color: #666; font-size: 12px; margin-top: 2px;">{{ Auth::user()->email }}</div>
                    </div>
                    @if(Auth::user()->role === 'admin')
                        <a href="/account-management" class="dropdown-menu-item"
                            style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: #23272f; font-size: 14px; font-weight: 500; text-decoration: none;">
                            <svg width="18" height="18" fill="none" stroke="#6366f1" stroke-width="1.7" viewBox="0 0 24 24">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                            Account Management
                        </a>
                        <a href="/new-approval-requests" class="dropdown-menu-item"
                            style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; color: #23272f; font-size: 14px; font-weight: 500; text-decoration: none;">
                            <svg width="18" height="18" fill="none" stroke="#6366f1" stroke-width="1.7" viewBox="0 0 24 24">
                                <rect x="4" y="4" width="16" height="16" rx="2" />
                                <path d="M9 9h6" stroke-linecap="round" />
                                <path d="M9 13h2l1 2l3-4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span style="flex: 1;">Approval Requests</span>
                            @if(isset($pendingApprovalCount) && $pendingApprovalCount > 0)
                                <span style="background: #ef4444; color: #fff; border-radius: 12px; padding: 2px 8px; font-size: 11px; font-weight: 600;">{{ $pendingApprovalCount }}</span>
                            @endif
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
<script>
    // Single source of truth for the bell/user dropdowns, shared by every page.
    document.addEventListener('DOMContentLoaded', function () {
        var bell = document.querySelector('.notification-bell');
        var notificationDropdown = document.getElementById('notificationDropdown');
        var userWrapper = document.querySelector('.user-wrapper');
        var userDropdown = document.getElementById('userDropdown');

        // Class toggles rather than inline style.display: an inline display
        // value outranks the stylesheet, which would make the open/close
        // transition in the CSS above impossible to apply.
        function closeDropdowns() {
            if (notificationDropdown) notificationDropdown.classList.remove('is-open');
            if (userDropdown) userDropdown.classList.remove('is-open');
        }

        if (bell && notificationDropdown) {
            bell.addEventListener('click', function (event) {
                event.stopPropagation();
                var willOpen = !notificationDropdown.classList.contains('is-open');
                closeDropdowns();
                notificationDropdown.classList.toggle('is-open', willOpen);
            });
        }

        if (userWrapper && userDropdown) {
            userWrapper.addEventListener('click', function (event) {
                event.stopPropagation();
                var willOpen = !userDropdown.classList.contains('is-open');
                closeDropdowns();
                userDropdown.classList.toggle('is-open', willOpen);
            });
        }

        // Close both dropdowns when clicking anywhere else on the page.
        // Clicks on links inside #userDropdown still bubble through the
        // .user-wrapper handler above, which closes it — same as before.
        document.addEventListener('click', closeDropdowns);

        // Off-canvas sidebar toggle for small screens
        var sidebarToggle = document.getElementById('sidebarToggle');
        var sidebar = document.querySelector('.sidebar');
        var backdrop = document.getElementById('sidebarBackdrop');

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (backdrop) backdrop.classList.remove('show');
        }

        function toggleSidebar(event) {
            event.stopPropagation();
            if (sidebar) sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('show');
        }

        if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        // Closing the menu after picking a page keeps the mobile nav from
        // covering the new page's content.
        if (sidebar) {
            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeSidebar);
            });
        }
    });
</script>