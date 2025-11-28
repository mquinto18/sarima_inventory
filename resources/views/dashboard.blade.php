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
    margin-top: 24px;
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
</style>
<div class="dashboard-gradient" style="margin-left: 220px; padding: 40px; padding-top: 90px; position: relative; overflow: hidden;">
    <!-- Large SVG Watermark Background -->
    <div style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); z-index: 0; pointer-events: none;">
        <svg width="700" height="700" viewBox="0 0 56 56" fill="none" style="opacity: 0.08;" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="crystalGradient" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#a5b4fc"/>
                    <stop offset="1" stop-color="#6366f1"/>
                </linearGradient>
            </defs>
            <circle cx="28" cy="28" r="20" fill="url(#crystalGradient)" stroke="#6366f1" stroke-width="2"/>
            <ellipse cx="28" cy="38" rx="12" ry="4" fill="#fff" fill-opacity=".25"/>
            <polyline points="16,36 22,28 28,32 34,20 40,26" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="22" cy="28" r="2" fill="#fff"/>
            <circle cx="28" cy="32" r="2" fill="#fff"/>
            <circle cx="34" cy="20" r="2" fill="#fff"/>
            <circle cx="40" cy="26" r="2" fill="#fff"/>
            <circle cx="16" cy="36" r="2" fill="#fff"/>
            <ellipse cx="28" cy="22" rx="6" ry="2" fill="#fff" fill-opacity=".18"/>
        </svg>
    </div>
    <div class="dashboard-title">Dashboard Overview</div>
    <div class="dashboard-subtitle">Key metrics and system status</div>
    <div class="dashboard-cards">
        <!-- Monthly Revenue -->
        <div class="dashboard-card" tabindex="0">
            <div class="icon" style="background: #e0fce6; color: #22c55e;">&#8369;</div>
            <div class="desc">Monthly Revenue</div>
            <div class="main">&#8369;{{ number_format($monthlyRevenue['current'], 2) }}</div>
            @if($monthlyRevenue['change_direction'] === 'increase')
                <div class="trend-up">↑ {{ number_format($monthlyRevenue['change_percentage'], 1) }}% vs last month</div>
            @else
                <div class="trend-down">↓ {{ number_format($monthlyRevenue['change_percentage'], 1) }}% vs last month</div>
            @endif
        </div>
        <!-- Total Products -->
        <div class="dashboard-card" tabindex="0">
            <div class="icon" style="background: #e0e7ff; color: #3b82f6;">&#128230;</div>
            <div class="desc">Total Products</div>
            <div class="main">{{ $totalProducts }}</div>
            <div class="sub">in inventory</div>
        </div>
        <!-- Low Stock Alerts -->
        <div class="dashboard-card" tabindex="0">
            <div class="icon" style="background: #fee2e2; color: #ef4444;">&#9888;</div>
            <div class="desc">Low Stock Alerts</div>
            <div class="main">{{ $dynamicReorderCount }}</div>
            <div class="sub">products need reorder</div>
        </div>
        <!-- Forecast Accuracy -->
        @php
            $statusClass = 'status-pending';
            if ($forecastAccuracy['accuracy_percentage'] >= 95) {
                $statusClass = 'status-excellent';
            } elseif ($forecastAccuracy['accuracy_percentage'] >= 85) {
                $statusClass = 'status-good';
            } elseif ($forecastAccuracy['accuracy_percentage'] > 0) {
                $statusClass = 'status-fair';
            }
        @endphp
        <div class="dashboard-card" tabindex="0">
            <div class="icon" style="background: #f3e8ff; color: #a21caf;">&#128200;</div>
            <div class="desc">Forecast Accuracy</div>
            <div class="main">
                @if($forecastAccuracy['accuracy_percentage'] > 0)
                    {{ number_format($forecastAccuracy['accuracy_percentage'], 1) }}%
                @else
                    --
                @endif
            </div>
            <div class="sub {{ $statusClass }}">{{ $forecastAccuracy['status'] }}</div>
        </div>
    </div>
</div>
