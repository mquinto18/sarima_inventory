@include('components.topheader')
@include('components.sidebar')

<div style="margin-left: 220px; padding: 40px; padding-top: 90px;">
    <h1 style="margin-bottom: 0.25em;">Dashboard Overview</h1>
    <p style="margin-top: 0; color: #666;">Key metrics and system status</p>

    <div style="display: flex; gap: 24px; margin-top: 32px;">
        <!-- Monthly Revenue -->
        <div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; flex-direction: column; min-width: 200px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #22c55e;">
                    &#36;
                </span>
                <div>
                    <span style="font-size: 1rem; color: #444;">Monthly Revenue</span>
                </div>
            </div>
            <div style="margin-top: 16px;">
                <span style="font-size: 1.5rem; font-weight: 600;">[Revenue]</span>
            </div>
            <div style="margin-top: 8px;">
                <a href="#" style="font-size: 0.95rem; color: #888; text-decoration: underline;">[Change]</a>
            </div>
        </div>
        <!-- Total Products -->
        <div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; flex-direction: column; min-width: 200px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #3b82f6;">
                    &#128230;
                </span>
                <div>
                    <span style="font-size: 1rem; color: #444;">Total Products</span>
                </div>
            </div>
            <div style="margin-top: 16px;">
                <span style="font-size: 1.5rem; font-weight: 600;">[Products]</span>
            </div>
            <div style="margin-top: 8px;">
                <a href="#" style="font-size: 0.95rem; color: #888; text-decoration: underline;">[Change]</a>
            </div>
        </div>
        <!-- Low Stock Alerts -->
        <div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; flex-direction: column; min-width: 200px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #ef4444;">
                    &#9888;
                </span>
                <div>
                    <span style="font-size: 1rem; color: #444;">Low Stock Alerts</span>
                </div>
            </div>
            <div style="margin-top: 16px;">
                <span style="font-size: 1.5rem; font-weight: 600;">[Alerts]</span>
            </div>
            <div style="margin-top: 8px;">
                <a href="#" style="font-size: 0.95rem; color: #888; text-decoration: underline;">[Change]</a>
            </div>
        </div>
        <!-- Forecast Accuracy -->
        <div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; flex-direction: column; min-width: 200px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #a21caf;">
                    &#128200;
                </span>
                <div>
                    <span style="font-size: 1rem; color: #444;">Forecast Accuracy</span>
                </div>
            </div>
            <div style="margin-top: 16px;">
                <span style="font-size: 1.5rem; font-weight: 600;">[Accuracy]</span>
            </div>
            <div style="margin-top: 8px;">
                <a href="#" style="font-size: 0.95rem; color: #888; text-decoration: underline;">[Change]</a>
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 24px; margin-top: 32px;">
        <!-- Sales Trend Chart -->
        <div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; min-width: 300px; display: flex; flex-direction: column;">
            <span style="font-size: 1.2rem; font-weight: 600; margin-bottom: 16px;">Sales Trend</span>
            <div style="flex: 1; background: #ededf2; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #888; font-size: 1.1rem; min-height: 180px;"></div>
        </div>
        <!-- Inventory Status Chart -->
        <div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; min-width: 300px; display: flex; flex-direction: column;">
            <span style="font-size: 1.2rem; font-weight: 600; margin-bottom: 16px;">Inventory Status</span>
            <div style="flex: 1; background: #ededf2; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #888; font-size: 1.1rem; min-height: 180px;"></div>
        </div>
    </div>
</div>