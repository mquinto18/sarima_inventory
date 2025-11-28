<header class="top-header-modern">

    <style>
    .top-header-modern {
        width: 100%;
        background: #ffffff;
        box-shadow: 0 2px 16px 0 rgba(31,41,55,0.06);
        padding: 0;
        min-height: 70px;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 2000;
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
    .main-title {
        font-size: 2rem;
        font-weight: 900;
        color: #1f2937;
        letter-spacing: -1px;
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
        z-index: 1001;
    }
    .notification-bell {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 10px;
        border-radius: 50%;
        background: none;
        transition: background 0.2s;
        min-width: 44px;
        min-height: 44px;
    }
    .notification-bell:hover {
        background: #e0e7ff44;
    }
    .notification-bell svg {
        stroke: #6366f1;
        width: 26px;
        height: 26px;
        transition: stroke 0.2s;
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
    }
    .notification-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(99,102,241,0.13);
        min-width: 320px;
        max-width: 400px;
        z-index: 1000;
        margin-top: 8px;
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
        box-shadow: 0 2px 8px 0 rgba(99,102,241,0.06);
        transition: box-shadow 0.18s;
    }
    .user-wrapper:hover {
        box-shadow: 0 4px 16px 0 rgba(99,102,241,0.13);
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
        box-shadow: 0 2px 8px 0 rgba(99,102,241,0.10);
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
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 2px 8px rgba(99,102,241,0.08);
        min-width: 180px;
        z-index: 1000;
        border-radius: 12px;
        margin-top: 8px;
    }
    </style>
    <div class="header-content">
        <div class="header-text">
            <div class="main-title">SARIMA VISION</div>
            <div class="subtitle">Sales Forecasting &amp; Inventory Management</div>
        </div>
        <div class="header-actions">
            <div class="notification-wrapper">
                <div class="notification-bell" onclick="toggleNotificationDropdown(event)">
                    <svg width="26" height="26" fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11c0-3.07-1.64-5.64-5-5.958V4a1 1 0 1 0-2 0v1.042C6.64 5.36 5 7.929 5 11v3.159c0 .538-.214 1.055-.595 1.436L3 17h5m7 0v1a3 3 0 1 1-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    @if(isset($reorderCount) && $reorderCount > 0)
                    <span>{{ $reorderCount > 9 ? '9+' : $reorderCount }}</span>
                    @endif
                </div>
                <!-- Notification Dropdown -->
                <div class="notification-dropdown" id="notificationDropdown">
                    <div style="padding: 12px 16px; border-bottom: 1px solid #eee; font-weight: 600; color: #23272f;">
                        Reorder Notifications
                        @if(isset($reorderCount) && $reorderCount > 0)
                        <span style="float: right; background: #ef4444; color: white; border-radius: 12px; padding: 2px 8px; font-size: 12px;">{{ $reorderCount }}</span>
                        @endif
                    </div>
                    <div style="max-height: 300px; overflow-y: auto;">
                        @if(isset($reorderNotifications) && count($reorderNotifications) > 0)
                        @foreach($reorderNotifications as $notification)
                        <div style="padding: 12px 16px; border-bottom: 1px solid #f5f5f5;" onmouseover="this.style.backgroundColor='#f9f9f9'" onmouseout="this.style.backgroundColor='transparent'">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if($notification['priority'] === 'High')
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></div>
                                @elseif($notification['priority'] === 'Medium')
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></div>
                                @else
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></div>
                                @endif
                                <div style="flex: 1;">
                                    <div style="font-weight: 500; color: #23272f; font-size: 14px;">{{ $notification['name'] }}</div>
                                    <div style="color: #666; font-size: 12px;">
                                        Stock: {{ $notification['current_stock'] }} | Need: {{ $notification['recommended_quantity'] }} units
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 11px; color: #ef4444; font-weight: 600;">{{ $notification['priority'] }}</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @else
                        <div style="padding: 20px 16px; text-align: center; color: #666;">
                            <div style="font-size: 24px; margin-bottom: 8px;">✅</div>
                            <div style="font-weight: 500;">All products are well-stocked!</div>
                            <div style="font-size: 12px; margin-top: 4px; color: #999;">No reorder notifications at this time</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="user-wrapper" onclick="toggleDropdown(event)">
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
                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="dropdown-item" style="width: 100%; text-align: left; background: none; border: none; display: block; padding: 10px 16px; color: #ef4444; text-decoration: none; cursor: pointer; font-size: 14px;" onmouseover="this.style.backgroundColor='#f9f9f9'" onmouseout="this.style.backgroundColor='transparent'">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
<script>
    function toggleDropdown(event) {
        event.stopPropagation();
        var dropdown = event.currentTarget.querySelector('.dropdown-menu');
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';

        // Close notification dropdown when opening user dropdown
        var notificationDropdown = document.getElementById('notificationDropdown');
        if (notificationDropdown) notificationDropdown.style.display = 'none';
    }

    function toggleNotificationDropdown(event) {
        console.log('Notification bell clicked!'); // Debug log
        event.stopPropagation();
        event.preventDefault();

        var dropdown = document.getElementById('notificationDropdown');
        console.log('Dropdown found:', dropdown); // Debug log

        if (dropdown) {
            var isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
            console.log('Dropdown display set to:', dropdown.style.display); // Debug log
        } else {
            console.error('Notification dropdown not found!');
        }

        // Close user dropdown when opening notification dropdown
        var userDropdown = document.getElementById('userDropdown');
        if (userDropdown) userDropdown.style.display = 'none';

        return false; // Prevent any default action
    }

    // Alternative event listener using event delegation
    document.addEventListener('click', function(event) {
        // Handle notification bell clicks
        if (event.target.closest('.notification-bell')) {
            toggleNotificationDropdown(event);
            return;
        }

        // Close dropdowns when clicking elsewhere
        var userDropdown = document.getElementById('userDropdown');
        var notificationDropdown = document.getElementById('notificationDropdown');

        if (userDropdown) userDropdown.style.display = 'none';
        if (notificationDropdown) notificationDropdown.style.display = 'none';
    });

    // Ensure the function is available globally
    window.toggleNotificationDropdown = toggleNotificationDropdown;
</script>