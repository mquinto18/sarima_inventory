@include('components.topheader')
@include('components.sidebar')

<div style="margin-left: 220px; padding: 40px; padding-top: 90px; background: #fafbfc; min-height: 100vh;">
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