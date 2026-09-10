<ul>
    <li>
        <a href="/" class="{{ request()->is('/') ? 'active' : '' }}" title="Dashboard">
            <span class="nav-icon">
                <!-- Dashboard Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="2" />
                    <rect x="14" y="3" width="7" height="7" rx="2" />
                    <rect x="14" y="14" width="7" height="7" rx="2" />
                    <rect x="3" y="14" width="7" height="7" rx="2" />
                </svg>
            </span>
            <span class="nav-label">Dashboard</span>
        </a>
    </li>
    @if(Auth::check() && Auth::user()->role !== 'staff')
        <li>
            <a href="/forecasting" class="{{ request()->is('forecasting') ? 'active' : '' }}" title="Forecasting">
                <span class="nav-icon">
                    <!-- Modern Forecasting Icon -->
                    <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                        <polyline points="3 17 9 11 13 15 21 7"
                            style="fill:none;stroke-linecap:round;stroke-linejoin:round;" />
                        <circle cx="21" cy="7" r="1.5" fill="#111" />
                    </svg>
                </span>
                <span class="nav-label">Forecasting</span>
            </a>
        </li>
    @endif
    <li>
        <a href="/inventory" class="{{ request()->is('inventory') ? 'active' : '' }}" title="Inventory">
            <span class="nav-icon">
                <!-- Inventory Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                    <rect x="3" y="7" width="18" height="13" rx="2" />
                    <path d="M16 3v4M8 3v4" />
                </svg>
            </span>
            <span class="nav-label">Inventory</span>
        </a>
    </li>
    <li>
        <a href="/pos" class="{{ request()->is('pos') ? 'active' : '' }}" title="Point of Sale">
            <span class="nav-icon">
                <!-- Point of Sale Icon -->
                <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                    <rect x="2" y="9" width="20" height="12" rx="2" />
                    <path d="M6 9V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v3" stroke-linecap="round" stroke-linejoin="round" />
                    <line x1="12" y1="13" x2="12" y2="17" stroke-linecap="round" />
                    <line x1="9" y1="15" x2="15" y2="15" stroke-linecap="round" />
                </svg>
            </span>
            <span class="nav-label">Point of Sale</span>
        </a>
    </li>
    @if(Auth::check() && Auth::user()->role !== 'staff')
        <li>
            <a href="/suppliers" class="{{ request()->is('suppliers') ? 'active' : '' }}" title="Suppliers">
                <span class="nav-icon">
                    <!-- Suppliers Icon -->
                    <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                        <rect x="1" y="7" width="13" height="10" rx="1" />
                        <path d="M14 10h4l3 3v4h-2" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="6" cy="19" r="2" />
                        <circle cx="17" cy="19" r="2" />
                    </svg>
                </span>
                <span class="nav-label">Suppliers</span>
            </a>
        </li>
        <li>
            <a href="/purchase-orders" class="{{ request()->is('purchase-orders*') ? 'active' : '' }}" title="Purchase Orders">
                <span class="nav-icon">
                    <!-- Purchase Orders Icon -->
                    <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                        <rect x="4" y="4" width="16" height="16" rx="2" />
                        <path d="M9 9h6M9 13h6M9 17h3" stroke-linecap="round" />
                    </svg>
                </span>
                <span class="nav-label">Purchase Orders</span>
            </a>
        </li>
        <!-- <li>
            <a href="/analytics" class="{{ request()->is('analytics') ? 'active' : '' }}" title="Analytics">
                <span class="nav-icon">
                    <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                        <path d="M3 3v18h18" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M7 15l4-5 3 3 5-7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="nav-label">Analytics</span>
            </a>
        </li> -->
        <li>
            <a href="{{ route('logs.pos') }}" class="{{ request()->is('logs/*') ? 'active' : '' }}" title="Transaction Logs">
                <span class="nav-icon">
                    <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                        <path d="M4 4h13l3 3v13a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M8 10h8M8 14h8M8 18h5" stroke-linecap="round" />
                    </svg>
                </span>
                <span class="nav-label">Transaction Logs</span>
            </a>
        </li>
        <li>
            <a href="/settings" class="{{ request()->is('settings') ? 'active' : '' }}" title="Settings">
                <span class="nav-icon">
                    <svg width="20" height="20" fill="none" stroke="#111" stroke-width="1.7" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                </span>
                <span class="nav-label">Settings</span>
            </a>
        </li>
    @endif
    <!-- Edit Requests link removed -->
</ul>
