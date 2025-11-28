@include('components.topheader')
@include('components.sidebar')


<style>
.dashboard-gradient {
    background: linear-gradient(120deg, #f8fafc 0%, #e0e7ff 100%);
    min-height: 100vh;
}
.dashboard-title {
    font-size: 2.3rem;
    font-weight: 800;
    margin-bottom: 0.2em;
    letter-spacing: -1px;
    color: #18181b;
}
.dashboard-subtitle {
    color: #6366f1;
    font-size: 1.1rem;
    margin-bottom: 2.5rem;
    font-weight: 500;
}
.dashboard-cards {
    display: flex;
    gap: 32px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}
.dashboard-card {
    flex: 1 1 220px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 24px 0 rgba(99,102,241,0.08);
    padding: 32px 28px 28px 28px;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 220px;
    transition: transform 0.18s cubic-bezier(.4,2,.6,1), box-shadow 0.18s cubic-bezier(.4,2,.6,1);
    border: none;
    position: relative;
    cursor: pointer;
}
.dashboard-card:hover {
    transform: translateY(-7px) scale(1.03);
    box-shadow: 0 8px 32px 0 rgba(99,102,241,0.16);
}
.dashboard-card .icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.1rem;
    margin-bottom: 10px;
}
.dashboard-card .main {
    font-size: 2rem;
    font-weight: 700;
    color: #18181b;
    margin-bottom: 0.2em;
}
.dashboard-card .desc {
    font-size: 1.08rem;
    color: #6366f1;
    font-weight: 600;
    margin-bottom: 0.5em;
}
.dashboard-card .sub {
    font-size: 1rem;
    color: #888;
    font-weight: 400;
}
.dashboard-card .trend-up {
    color: #22c55e;
    font-weight: 600;
    font-size: 1.02rem;
}
.dashboard-card .trend-down {
    color: #ef4444;
    font-weight: 600;
    font-size: 1.02rem;
}
.dashboard-card .status-excellent { color: #22c55e; font-weight: 600; }
.dashboard-card .status-good { color: #3b82f6; font-weight: 600; }
.dashboard-card .status-fair { color: #f59e0b; font-weight: 600; }
.dashboard-card .status-pending { color: #888; font-weight: 600; }
.dashboard-charts {
    display: flex;
    gap: 32px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}
.dashboard-chart-card {
    flex: 2 1 320px;
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    min-width: 320px;
    box-shadow: 0 2px 12px 0 rgba(99,102,241,0.06);
    margin-bottom: 0;
}
.dashboard-chart-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 16px;
    color: #23272f;
}
.dashboard-chart-content {
    background: #ededf2;
    border-radius: 12px;
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #888;
    font-size: 1.1rem;
}
</style>
<div class="dashboard-gradient" style="margin-left: 220px; padding: 40px; padding-top: 90px;">
    <div class="dashboard-title">Analytics</div>
    <div class="dashboard-subtitle">Performance insights and AI recommendations</div>
    <div class="dashboard-cards">
        <div class="dashboard-card" tabindex="0">
            <div class="icon" style="background: #e0fce6; color: #22c55e;">&#36;</div>
            <div class="desc">[Revenue]</div>
            <div class="main">Total Sales YTD</div>
            <div class="trend-up">[% Change]</div>
        </div>
        <div class="dashboard-card" tabindex="0">
            <div class="icon" style="background: #e0e7ff; color: #3b82f6;">&#128161;</div>
            <div class="desc">[Accuracy]</div>
            <div class="main">Forecast Accuracy</div>
            <div class="sub">SARIMA Performance</div>
        </div>
        <div class="dashboard-card" tabindex="0">
            <div class="icon" style="background: #f3e8ff; color: #a21caf;">&#128200;</div>
            <div class="desc">[Rate]</div>
            <div class="main">Inventory Turnover</div>
            <div class="sub">[Change]</div>
        </div>
    </div>
    <div class="dashboard-charts">
        <div class="dashboard-chart-card">
            <div class="dashboard-chart-title">Performance Trends</div>
            <div class="dashboard-chart-content">[Chart Placeholder]</div>
        </div>
        <div class="dashboard-chart-card">
            <div class="dashboard-chart-title">Category Analysis</div>
            <div class="dashboard-chart-content">[Chart Placeholder]</div>
        </div>
    </div>
</div>

    <!-- Key Metrics Table -->
    <div style="background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; margin-bottom: 32px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">

        <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Key Metrics</div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #fafbfc;">
                        <th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Metric</th>
                        <th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Current</th>
                        <th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Previous</th>
                        <th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Change</th>
                        <th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Target</th>
                        <th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px 8px;">Total Revenue</td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 8px;">Forecast Accuracy</td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 8px;">Inventory Turnover</td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                        <td style="padding: 10px 8px;"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display: flex; gap: 32px;">
        <!-- AI Insights -->
        <div style="flex: 1; background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">AI Insights</div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <div style="background: #e0e7ff; color: #3730a3; border-radius: 8px; padding: 10px 16px; font-size: 1rem;">[AI Insight 1]</div>
                <div style="background: #e0e7ff; color: #3730a3; border-radius: 8px; padding: 10px 16px; font-size: 1rem;">[AI Insight 2]</div>
                <div style="background: #e0e7ff; color: #3730a3; border-radius: 8px; padding: 10px 16px; font-size: 1rem;">[AI Insight 3]</div>
            </div>
        </div>
        <!-- Recommendations -->
        <div style="flex: 1; background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Recommendations</div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <div style="background: #dcfce7; color: #15803d; border-radius: 8px; padding: 10px 16px; font-size: 1rem;">[Recommendation 1]</div>
                <div style="background: #dcfce7; color: #15803d; border-radius: 8px; padding: 10px 16px; font-size: 1rem;">[Recommendation 2]</div>
                <div style="background: #dcfce7; color: #15803d; border-radius: 8px; padding: 10px 16px; font-size: 1rem;">[Recommendation 3]</div>
            </div>
        </div>
    </div>
</div>