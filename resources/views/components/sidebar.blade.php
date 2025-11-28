<nav class="sidebar">
    @include('components.menu')
</nav>

<style>
.sidebar {
    width: 220px;
    height: calc(100vh - 70px);
    background: #f8fafc;
    color: #374151;
    position: fixed;
    top: 70px;
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