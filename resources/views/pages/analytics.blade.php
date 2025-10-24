@include('components.topheader')
@include('components.sidebar')

<div style="margin-left: 220px; padding: 40px; padding-top: 90px; background: #fafbfc; min-height: 100vh;">
    <h1 style="margin-bottom: 0.25em;">Analytics</h1>
    <p style="margin-top: 0; color: #666;">Performance insights and AI recommendations</p>
    <!-- Summary Cards -->
    <div style="display: flex; gap: 32px; margin-bottom: 32px;">
        <div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 32px 24px 24px 24px; background: #fff; display: flex; flex-direction: column; align-items: center;">
            <span style="display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: #22c55e; color: #fff; font-size: 2rem; margin-bottom: 8px;">&#36;</span>
            <div style="font-size: 1.3rem; font-weight: 600; margin-bottom: 4px;">[Revenue]</div>
            <div style="color: #888; font-size: 1rem;">Total Sales YTD</div>
            <div style="color: #22c55e; font-size: 1rem; margin-top: 8px;">[% Change]</div>
        </div>
        <div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 32px 24px 24px 24px; background: #fff; display: flex; flex-direction: column; align-items: center;">
            <span style="display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: #3b82f6; color: #fff; font-size: 2rem; margin-bottom: 8px;">&#128161;</span>
            <div style="font-size: 1.3rem; font-weight: 600; margin-bottom: 4px;">[Accuracy]</div>
            <div style="color: #888; font-size: 1rem;">Forecast Accuracy</div>
            <div style="color: #3b82f6; font-size: 1rem; margin-top: 8px;">SARIMA Performance</div>
        </div>
        <div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 32px 24px 24px 24px; background: #fff; display: flex; flex-direction: column; align-items: center;">
            <span style="display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: #a21caf; color: #fff; font-size: 2rem; margin-bottom: 8px;">&#128200;</span>
            <div style="font-size: 1.3rem; font-weight: 600; margin-bottom: 4px;">[Rate]</div>
            <div style="color: #888; font-size: 1rem;">Inventory Turnover</div>
            <div style="color: #a21caf; font-size: 1rem; margin-top: 8px;">[Change]</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display: flex; gap: 32px; margin-bottom: 32px;">
        <div style="flex: 2; background: #fff; border-radius: 16px; padding: 24px; display: flex; flex-direction: column;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Performance Trends</div>
            <div style="background: #ededf2; border-radius: 12px; min-height: 180px; display: flex; align-items: center; justify-content: center; color: #888; font-size: 1.1rem;"></div>
        </div>
        <div style="flex: 2; background: #fff; border-radius: 16px; padding: 24px; display: flex; flex-direction: column;">
            <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Category Analysis</div>
            <div style="background: #ededf2; border-radius: 12px; min-height: 180px; display: flex; align-items: center; justify-content: center; color: #888; font-size: 1.1rem;"></div>
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