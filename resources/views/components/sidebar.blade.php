<nav class="sidebar">
    @include('components.menu')
    <ul>
        <li>
            <form method="POST" action="{{ route('logout') }}" style="margin: 0; ;">
                @csrf
                <button type="submit" style="width: 100%; text-align: left; background: none; border: none; display: flex; align-items: center; gap: 10px; padding: 13px 26px; color: #ef4444; font-size: 1.13rem; font-weight: 600; border-radius: 10px; cursor: pointer;">
                    <span>
                        <!-- Logout Icon -->
                        <svg width="20" height="20" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M16 17l5-5m0 0l-5-5m5 5H9" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    Logout
                </button>
            </form>
        </li>
    </ul>
</nav>

<style>
.sidebar {
    width: 220px;
    height: calc(100vh - 70px);
    background: #f8fafc;
    color: #374151;
    position: fixed;
    top: 70px;
                            <div class="sidebar-logout">
                                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                                    @csrf
                                    <button type="submit" style="width: 100%; text-align: left; background: none; border: none; display: flex; align-items: center; gap: 10px; padding: 13px 26px; color: #ef4444; font-size: 1.13rem; font-weight: 600; border-radius: 10px; cursor: pointer;">
                                        <span>
                                            <!-- Logout Icon -->
                                            <svg width="20" height="20" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
                                                <path d="M16 17l5-5m0 0l-5-5m5 5H9" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M13 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        Logout
                                    </button>
                                </form>
                            </div>
    left: 0;

    padding-top: 40px;
    padding-left: 10px;
    padding-right: 10px;
    border-right: none;
    box-shadow: 2px 0 24px 0 rgba(107,114,128,0.07);
    z-index: 100;
    backdrop-filter: blur(6px);
    /* glass effect */
}
.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar ul li {
    margin-bottom: 18px;
}
.sidebar ul li a {
    color: #374151;
    text-decoration: none;
    font-size: 1.13rem;
    padding: 13px 26px;
    display: flex;
    align-items: center;
    border-radius: 10px;
    font-weight: 600;
    gap: 10px;
    transition: background 0.18s, color 0.18s;
    box-shadow: 0 1px 4px 0 rgba(99,102,241,0.03);
}
.sidebar ul li a span svg {
    stroke: #2563eb !important; 
    fill: none !important;
    transition: stroke 0.2s, fill 0.2s;
    opacity: 0.85;
}
.sidebar ul li a.active span svg,
.sidebar ul li a:hover span svg {
    stroke: #2563eb !important;
    fill: #2563eb !important;
    opacity: 1;
    filter: drop-shadow(0 0 4px #2563eb33);
}
.sidebar ul li a:hover,
.sidebar ul li a.active {
    background: #e5e7eb;
    color: #2563eb;
    box-shadow: 0 4px 16px 0 rgba(31,41,55,0.06);
}
</style>