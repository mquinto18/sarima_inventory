@include('components.topheader')
@include('components.sidebar')

<div style="margin-left: 220px; padding: 40px; padding-top: 90px; background: #fafbfc; min-height: 100vh;">
	<!-- Filter Bar -->
	<div style="display: flex; gap: 16px; align-items: center; background: #fff; border-radius: 16px; padding: 20px 24px; margin-bottom: 32px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
		<select style="padding: 10px 16px; border-radius: 8px; border: none; background: #f3f4f6; color: #555; font-size: 1rem; min-width: 180px;">
			<option>Select Product Category</option>
		</select>
		<select style="padding: 10px 16px; border-radius: 8px; border: none; background: #f3f4f6; color: #555; font-size: 1rem; min-width: 160px;">
			<option>Forecast Period</option>
		</select>
		<button style="display: flex; align-items: center; gap: 8px; background: #11111a; color: #fff; border: none; border-radius: 8px; padding: 10px 22px; font-size: 1rem; font-weight: 600; cursor: pointer;">
			<span style="font-size: 1.1rem;">&#128200;</span> Generate Forecast
		</button>
	</div>

	<!-- Forecast Visualization Card -->
	<div style="background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; margin-bottom: 32px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
		<div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Forecast Visualization</div>
		<div style="background: #ededf2; border-radius: 12px; min-height: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #888;">
			<span style="font-size: 3rem; margin-bottom: 8px;">&#128202;</span>
			<div style="font-size: 1.2rem; font-weight: 500; color: #444;">SARIMA Forecast Chart</div>
			<div style="font-size: 1rem; color: #888;">Historical + Predicted Sales</div>
		</div>
	</div>

	<!-- Detailed Predictions Table -->
	<div style="background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
		<div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Detailed Predictions</div>
		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="background: #fafbfc;">
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Period</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Predicted Sales</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Lower Bound</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Upper Bound</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Confidence</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px; color: #ef4444;"></td>
						<td style="padding: 10px 8px; color: #22c55e;"></td>
						<td style="padding: 10px 8px;"></td>
					</tr>
					<tr>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px; color: #ef4444;"></td>
						<td style="padding: 10px 8px; color: #22c55e;"></td>
						<td style="padding: 10px 8px;"></td>
					</tr>
					<!-- Add more rows as needed -->
				</tbody>
			</table>
					<!-- Data rows will be dynamically inserted here -->
	</div>
