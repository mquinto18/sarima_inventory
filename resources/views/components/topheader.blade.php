<header class="top-header">
    <div class="header-content">
        <div class="header-text">
            <div class="main-title">SARIMA Analytics Dashboard</div>
            <div class="subtitle">Sales Forecasting &amp; Inventory Management</div>
        </div>
        <div class="header-actions">
            <div class="notification-wrapper">
                <span class="notification-bell">
                    <svg width="22" height="22" fill="none" stroke="#23272f" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11c0-3.07-1.64-5.64-5-5.958V4a1 1 0 1 0-2 0v1.042C6.64 5.36 5 7.929 5 11v3.159c0 .538-.214 1.055-.595 1.436L3 17h5m7 0v1a3 3 0 1 1-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </div>
            <div class="user-wrapper">
                <span class="user-avatar">A</span>
                <div class="user-info">
                    <span class="user-name">Admin User</span><br>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
    .top-header {
        width: 100%;
        height: 70px;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        display: flex;
        align-items: center;
        position: fixed;
        top: 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.10);
        left: 0;
        z-index: 100;
    }

    .header-content {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 40px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 32px;
    }

    .notification-wrapper {
        position: relative;
        margin-right: 16px;
    }

    .notification-bell {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .notification-badge {
        position: absolute;
        top: -8px;
        right: -10px;
        background: #11111a;
        color: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    }

    .user-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        background: #11111a;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        font-weight: 700;
    }

    .user-info {
        flex-direction: column;
        align-items: flex-start;

        gap: 0;
        margin: 0;
    }

    .user-name {
        margin-bottom: 0;
    }

    .user-name {
        font-size: 1rem;
        font-weight: 600;
        color: #23272f;
    }

    .user-role {
        font-size: 0.95rem;
        color: #7c7c8a;
    }

    .header-text {
        text-align: left;
    }

    .main-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #23272f;
    }

    .subtitle {
        font-size: 1rem;
        color: #394150;
    }
</style>