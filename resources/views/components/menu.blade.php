<ul>
    <li>
        <a href="/" class="{{ request()->is('/') ? 'active' : '' }}">
            <span style="vertical-align: middle; margin-right: 10px; color: #111;">
                <!-- Dashboard Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="2" />
                    <rect x="14" y="3" width="7" height="7" rx="2" />
                    <rect x="14" y="14" width="7" height="7" rx="2" />
                    <rect x="3" y="14" width="7" height="7" rx="2" />
                </svg>
            </span>
            Dashboard
        </a>
    </li>
    @if(Auth::check() && Auth::user()->role !== 'staff')
    <li>
        <a href="/forecasting" class="{{ request()->is('forecasting') ? 'active' : '' }}" title="Forecasting/Analytics" style="display: flex; align-items: center; gap: 8px; min-width: 0; overflow: hidden; text-overflow: ellipsis; font-size: 0.93rem;">
            <span style="display: flex; align-items: center; flex-shrink: 0;">
                <!-- Modern Forecasting Icon -->
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="margin-right: 4px;">
                    <polyline points="3 17 9 11 13 15 21 7" style="fill:none;stroke-linecap:round;stroke-linejoin:round;" />
                    <circle cx="21" cy="7" r="1.5" :fill="'currentColor'" />
                </svg>
            </span>
            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; max-width: 180px;">Forecasting/Analytics</span>
        </a>
    </li>
    @endif
    <li>
        <a href="/inventory" class="{{ request()->is('inventory') ? 'active' : '' }}" style="display: flex; align-items: center; gap: 8px; min-width: 0; overflow: hidden; text-overflow: ellipsis; font-size: 0.93rem;">
            <span style="vertical-align: middle; margin-right: 10px; color: #111;">
                <!-- Inventory Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                    <rect x="3" y="7" width="18" height="13" rx="2" />
                    <path d="M16 3v4M8 3v4" />
                </svg>
            </span>
            Inventory
        </a>
    </li>
    @if(Auth::check() && Auth::user()->role !== 'staff')
    <!-- Analytics tab removed -->
    <!-- <li>
        <a href="/settings" class="{{ request()->is('settings') ? 'active' : '' }}">
            <span style="vertical-align: middle; margin-right: 10px; color: #111;">
              
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="3" />
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                </svg>
            </span>
            Settings
        </a>
    </li> -->
    @endif
    @if(Auth::check() && Auth::user()->role === 'admin')
    <li>
        <a href="/account-management" class="{{ request()->is('account-management') ? 'active' : '' }}">
            <span style="vertical-align: middle; margin-right: 10px; color: #111;">
                <!-- Account Management Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            </span>
            Account Management
        </a>
    </li>
    @endif
    <!-- Edit Requests link removed -->
</ul>