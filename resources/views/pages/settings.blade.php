@include('components.topheader')
@include('components.sidebar')

<div style="margin-left: 220px; padding: 40px; padding-top: 90px; background: #fafbfc; min-height: 100vh; position: relative;">


    <!-- Modern User Profile Card -->
    <div style="position: absolute; top: 40px; right: 40px; z-index: 10;">
        <div style="background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.10); padding: 28px 36px 28px 28px; min-width: 320px; display: flex; align-items: center; gap: 22px; border: 1.5px solid #f3f4f6;">
            <div style="background: linear-gradient(135deg, #6366f1 60%, #818cf8 100%); border-radius: 50%; width: 72px; height: 72px; display: flex; align-items: center; justify-content: center; font-size: 2.3rem; color: #fff; font-weight: 700; box-shadow: 0 2px 8px rgba(99,102,241,0.10);">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div style="flex:1; min-width:0;">
                <div style="font-weight: 700; font-size: 1.18rem; color: #23272f; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ Auth::user()->name }}</div>
                <div style="font-size: 1.01rem; color: #6366f1; font-weight: 500; margin-bottom: 2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ Auth::user()->email }}</div>
                <div style="font-size: 0.97rem; color: #888; margin-bottom: 6px;">Role: <span style="color: #6366f1; font-weight: 600;">{{ ucfirst(Auth::user()->role) }}</span></div>
                <div style="position: relative;">
                    <button onclick="document.getElementById('userDropdown').classList.toggle('show')" style="background: #f3f4f6; color: #23272f; border: none; border-radius: 6px; padding: 6px 16px; font-size: 0.97rem; font-weight: 500; cursor: pointer; transition: background 0.2s;">Account ▾</button>
                    <div id="userDropdown" class="user-dropdown" style="display: none; position: absolute; right: 0; top: 110%; background: #fff; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.10); min-width: 160px; z-index: 100;">
                        <a href="/profile" style="display: block; padding: 10px 18px; color: #23272f; text-decoration: none; font-size: 0.97rem; border-bottom: 1px solid #f3f4f6;">Profile</a>
                        <a href="/settings" style="display: block; padding: 10px 18px; color: #23272f; text-decoration: none; font-size: 0.97rem; border-bottom: 1px solid #f3f4f6;">Settings</a>
                        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                            @csrf
                            <button type="submit" style="width: 100%; background: none; border: none; color: #ef4444; padding: 10px 18px; text-align: left; font-size: 0.97rem; cursor: pointer;">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple dropdown toggle (no jQuery needed)
        window.addEventListener('click', function(e) {
            var dropdown = document.getElementById('userDropdown');
            if (!dropdown) return;
            if (e.target.matches('button[onclick]')) return;
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
                dropdown.style.display = 'none';
            } else {
                dropdown.style.display = dropdown.classList.contains('show') ? 'block' : 'none';
            }
        });
        document.querySelector('button[onclick]').addEventListener('click', function(e) {
            var dropdown = document.getElementById('userDropdown');
            if (dropdown.classList.toggle('show')) {
                dropdown.style.display = 'block';
            } else {
                dropdown.style.display = 'none';
            }
            e.stopPropagation();
        });
    </script>

    <h1 style="margin-bottom: 0.25em;">System Settings</h1>
    <p style="margin-top: 0; color: #666;">Configure forecasting parameters and system preferences</p>

    <div style="display: flex; gap: 32px; margin-bottom: 32px;">
        <!-- Forecasting Configuration -->
        <div style="flex: 1; background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Forecasting Configuration</div>
            <div style="margin-bottom: 16px;">Default Forecast Period</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
            <div style="margin-bottom: 16px;">Confidence Interval</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
            <div style="margin-bottom: 16px;">Model Update Frequency</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
        </div>
        <!-- Inventory Alerts -->
        <div style="flex: 1; background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Inventory Alerts</div>
            <div style="margin-bottom: 16px;">Low Stock Threshold</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
            <div style="margin-bottom: 16px;">Critical Stock Level</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
            <div style="margin-bottom: 16px;">Auto Reorder</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
        </div>
    </div>

    <div style="display: flex; gap: 32px;">
        <!-- Notifications -->
        <div style="flex: 1; background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Notifications</div>
            <div style="margin-bottom: 16px;">Email Alerts</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
            <div style="margin-bottom: 16px;">Alert Frequency</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
        </div>
        <!-- Data Management -->
        <div style="flex: 1; background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Data Management</div>
            <div style="margin-bottom: 16px;">Data Retention</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
            <div style="margin-bottom: 16px;">Backup Frequency</div>
            <div style="background: #f3f4f6; border-radius: 6px; padding: 10px 16px; margin-bottom: 12px;"></div>
        </div>
    </div>
</div>