<nav class="sidebar">
    @include('components.menu')
</nav>

<style>
    .sidebar ul li a.active span svg,
    .sidebar ul li a:hover span svg {
        stroke: #fff !important;
    }

    .sidebar {
        width: 220px;
        height: calc(100vh - 70px);
        background: #f5f5f5;
        color: #111;
        position: fixed;
        top: 70px;
        left: 0;
        padding-top: 40px;
        padding-left: 10px;
        padding-right: 10px;
        border-right: 1px solid rgba(0, 0, 0, 0.10);
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
        color: #111;
        text-decoration: none;
        font-size: 1.1rem;
        padding: 12px 24px;
        display: block;
        border-radius: 6px;
        transition: background 0.2s;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
        background: #030213;
        color: #ffffff;
    }
</style>