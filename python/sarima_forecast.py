import sys
import json
import pandas as pd
from statsmodels.tsa.statespace.sarimax import SARIMAX

# Read JSON input from stdin
input_data = json.load(sys.stdin)

# Parse sales data
sales = input_data['sales']  # List of {"month": "YYYY-MM", "revenue": float}
forecast_period = input_data.get('forecast_period', 6)

# Prepare data
months = [item['month'] for item in sales]
revenues = [item['revenue'] for item in sales]
df = pd.DataFrame({'month': months, 'revenue': revenues})
df['month'] = pd.to_datetime(df['month'])
df.set_index('month', inplace=True)

# Fit SARIMA model (auto order for demo, you can tune these)
order = (1, 1, 1)
seasonal_order = (1, 1, 1, 12)
model = SARIMAX(df['revenue'], order=order, seasonal_order=seasonal_order, enforce_stationarity=False, enforce_invertibility=False)
results = model.fit(disp=False)

# Forecast
forecast = results.get_forecast(steps=forecast_period)
forecast_index = pd.date_range(df.index[-1] + pd.offsets.MonthBegin(), periods=forecast_period, freq='MS')
forecast_values = forecast.predicted_mean.values
conf_int = forecast.conf_int(alpha=0.05)

# Output
output = {
    'months': [d.strftime('%b %Y') for d in forecast_index],
    'predicted': [float(x) for x in forecast_values],
    'conf_lower': [float(x) for x in conf_int.iloc[:, 0]],
    'conf_upper': [float(x) for x in conf_int.iloc[:, 1]],
}
print(json.dumps(output))
