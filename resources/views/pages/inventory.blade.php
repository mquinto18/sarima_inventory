@include('components.topheader')
@include('components.sidebar')

<div style="margin-left: 220px; padding: 40px; padding-top: 90px; background: #fafbfc; min-height: 100vh;">
	<h1 style="margin-bottom: 0.25em;">Inventory Management</h1>
	<p style="margin-top: 0; color: #666;">Stock levels and automated reorder recommendations</p>

	<!-- Summary Cards -->
	<div style="display: flex; gap: 24px; margin-top: 32px; margin-bottom: 32px;">
		<div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; align-items: center; gap: 16px; min-width: 180px;">
			<span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #3b82f6;">&#128230;</span>
			<div>
				<span style="font-size: 1rem; color: #444;">Total Products</span><br>
				<span style="font-size: 1.2rem; font-weight: 600;">[Count]</span>
			</div>
		</div>
		<div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; align-items: center; gap: 16px; min-width: 180px;">
			<span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #ef4444;">&#9888;</span>
			<div>
				<span style="font-size: 1rem; color: #444;">Low Stock</span><br>
				<span style="font-size: 1.2rem; font-weight: 600;">[Count]</span>
			</div>
		</div>
		<div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; align-items: center; gap: 16px; min-width: 180px;">
			<span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #ff9800;">&#9888;</span>
			<div>
				<span style="font-size: 1rem; color: #444;">Critical</span><br>
				<span style="font-size: 1.2rem; font-weight: 600;">[Count]</span>
			</div>
		</div>
		<div style="flex: 1; border: 2px solid #e0e0e0; border-radius: 16px; padding: 24px; background: none; display: flex; align-items: center; gap: 16px; min-width: 180px;">
			<span style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; background: #f5f5f5; font-size: 1.7rem; color: #22c55e;">&#36;</span>
			<div>
				<span style="font-size: 1rem; color: #444;">Total Value</span><br>
				<span style="font-size: 1.2rem; font-weight: 600;">[Value]</span>
			</div>
		</div>
	</div>

	<!-- Search and Actions Bar -->
	<div style="display: flex; align-items: center; gap: 12px; background: #fff; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
		<input type="text" placeholder="Search products..." style="flex: 1; padding: 10px 16px; border-radius: 8px; border: none; background: #f3f4f6; color: #555; font-size: 1rem;" />
		<button style="background: #fff; color: #23272f; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px 18px; font-size: 1rem; font-weight: 500; cursor: pointer;">Filter</button>
		<button style="background: #11111a; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-size: 1rem; font-weight: 600; cursor: pointer;">Add Product</button>
	</div>

	<!-- Current Inventory Table -->
	<div style="background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; margin-bottom: 32px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
		<div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Current Inventory</div>
		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="background: #fafbfc;">
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Product ID</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Name</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Category</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Stock</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Status</th>
						<th style="padding: 12px 8px; text-align: left; font-weight: 600; color: #23272f;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"><button style="background: #fff; color: #23272f; border: 1px solid #e0e0e0; border-radius: 8px; padding: 6px 16px; font-size: 1rem; font-weight: 500; cursor: pointer;">Edit</button></td>
					</tr>
					<tr>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"><button style="background: #fff; color: #23272f; border: 1px solid #e0e0e0; border-radius: 8px; padding: 6px 16px; font-size: 1rem; font-weight: 500; cursor: pointer;">Edit</button></td>
					</tr>
					<tr>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"></td>
						<td style="padding: 10px 8px;"><button style="background: #fff; color: #23272f; border: 1px solid #e0e0e0; border-radius: 8px; padding: 6px 16px; font-size: 1rem; font-weight: 500; cursor: pointer;">Edit</button></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Reorder Recommendations -->
	<div style="background: #fff; border-radius: 16px; padding: 24px 24px 32px 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
		<div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #23272f;">Reorder Recommendations</div>
		<div style="display: flex; flex-direction: column; gap: 12px;">
			<div style="display: flex; align-items: center; background: #f6f6fa; border-radius: 10px; padding: 18px 20px; gap: 18px;">
				<div style="flex: 1;">
					<div style="font-weight: 500;">[Product 1]</div>
					<div style="font-size: 0.98rem; color: #888;">Recommended reorder</div>
				</div>
				<span style="background: #ef4444; color: #fff; border-radius: 8px; padding: 4px 16px; font-size: 1rem; font-weight: 500;">High</span>
				<span style="color: #22c55e; font-size: 1rem; font-weight: 600;">[Amount]</span>
				<button style="background: #11111a; color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-size: 1rem; font-weight: 600; cursor: pointer;">Approve</button>
			</div>
			<div style="display: flex; align-items: center; background: #f6f6fa; border-radius: 10px; padding: 18px 20px; gap: 18px;">
				<div style="flex: 1;">
					<div style="font-weight: 500;">[Product 2]</div>
					<div style="font-size: 0.98rem; color: #888;">Recommended reorder</div>
				</div>
				<span style="background: #fde68a; color: #b45309; border-radius: 8px; padding: 4px 16px; font-size: 1rem; font-weight: 500;">Medium</span>
				<span style="color: #22c55e; font-size: 1rem; font-weight: 600;">[Amount]</span>
				<button style="background: #11111a; color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-size: 1rem; font-weight: 600; cursor: pointer;">Approve</button>
			</div>
		</div>
	</div>
</div>
