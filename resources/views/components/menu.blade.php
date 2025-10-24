<ul>
    <li>
        <a href="/" class="{{ request()->is('/') ? 'active' : '' }}">
            <span style="vertical-align: middle; margin-right: 10px; color: #111;">
                <!-- Dashboard Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/></svg>
            </span>
            Dashboard
        </a>
    </li>
    <li>
        <a href="/forecasting" class="{{ request()->is('forecasting') ? 'active' : '' }}">
            <span style="vertical-align: middle; margin-right: 10px; color: #111;">
                <!-- Forecasting Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 8-8"/><circle cx="17" cy="7" r="1.5"/></svg>
            </span>
            Forecasting
        </a>
    </li>
    <li>
        <a href="/inventory" class="{{ request()->is('inventory') ? 'active' : '' }}">
            <span style="vertical-align: middle; margin-right: 10px; color: #111;">
                <!-- Inventory Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M16 3v4M8 3v4"/></svg>
            </span>
            Inventory
        </a>
    </li>
    <li>
        <a href="/analytics" class="{{ request()->is('analytics') ? 'active' : '' }}">
            <span style="vertical-align: middle; margin-right: 10px; color: #111;">
                <!-- Analytics Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24"><rect x="3" y="12" width="4" height="8" rx="1"/><rect x="10" y="8" width="4" height="12" rx="1"/><rect x="17" y="4" width="4" height="16" rx="1"/></svg>
            </span>
            Analytics
        </a>
    </li>
    <li>
        <a href="/settings" class="{{ request()->is('settings') ? 'active' : '' }}">
            <span style="vertical-align: middle; margin-right: 10px; color: #111;">
                <!-- Settings Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </span>
            Settings
        </a>
    </li>
</ul>
