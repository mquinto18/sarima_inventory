<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SARIMA Inventory')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>

<body>
    @if (!Request::is('login'))
        <script>
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.body.classList.add('sidebar-collapsed');
            }
        </script>
        @include('components.topheader')
        @include('components.sidebar')
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <main class="app-main">
            @yield('content')
        </main>
        @include('components.ui-kit')

        {{-- Only on the first page after a successful sign-in. --}}
        @if (session('justLoggedIn'))
            @include('components.login-splash')
        @endif

        {{-- Flashed messages surface as a toast rather than an inline banner,
             so every page reports the result of a redirect the same way and no
             page has to render its own banner markup. @json is safe in script
             context; showToast writes via textContent, so the message is safe
             in the DOM too. --}}
        @if (session('success') || session('error') || $errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    @if (session('success'))
                        showToast(@json(session('success')), 'success');
                    @elseif (session('error'))
                        showToast(@json(session('error')), 'error');
                    @else
                        showToast(@json($errors->first()), 'error');
                    @endif
                });
            </script>
        @endif
    @else
        <main>
            @yield('content')
        </main>
    @endif
    @stack('scripts')
</body>

</html>
