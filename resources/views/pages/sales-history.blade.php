<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History - Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/topheader.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="bg-light">
    @include('navbar')

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">📈 Sales History</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                            <li class="breadcrumb-item active">Sales History</li>
                        </ol>
                    </nav>
                </div>

                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h5 class="card-title text-primary">📊 Total Sales</h5>
                                <h2 class="text-primary mb-0">{{ number_format($totalSales) }}</h2>
                                <small class="text-muted">Total transactions</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h5 class="card-title text-success">💰 Total Revenue</h5>
                                <h2 class="text-success mb-0">₱{{ number_format($totalRevenue, 2) }}</h2>
                                <small class="text-muted">All time revenue</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h5 class="card-title text-info">📋 Average Order</h5>
                                <h2 class="text-info mb-0">₱{{ number_format($averageOrderValue, 2) }}</h2>
                                <small class="text-muted">Per transaction</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">🛍️ Recent Sales Records</h5>
                    </div>
                    <div class="card-body">
                        @if($sales->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Sale ID</th>
                                        <th>Product</th>
                                        <th>Quantity Sold</th>
                                        <th>Unit Price</th>
                                        <th>Total Amount</th>
                                        <th>Sale Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sales as $sale)
                                    <tr>
                                        <td>
                                            <span class="badge badge-secondary">#{{ $sale->id }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $sale->product->name ?? 'Unknown Product' }}</strong>
                                            @if($sale->product)
                                            <br><small class="text-muted">SKU: {{ $sale->product->id }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ number_format($sale->quantity_sold) }}</span>
                                        </td>
                                        <td>₱{{ number_format($sale->unit_price, 2) }}</td>
                                        <td>
                                            <strong class="text-success">₱{{ number_format($sale->total_amount, 2) }}</strong>
                                        </td>
                                        <td>
                                            {{ $sale->sale_date->format('M d, Y') }}
                                            <br><small class="text-muted">{{ $sale->created_at->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">Completed</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $sales->links() }}
                        </div>
                        @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Sales Records Found</h5>
                            <p class="text-muted">No sales have been recorded yet. Start by recording your first sale!</p>
                            <a href="/forecasting" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Record New Sale
                            </a>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6>Quick Actions</h6>
                                <div class="btn-group" role="group">
                                    <a href="/forecasting" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Record New Sale
                                    </a>
                                    <a href="/forecasting" class="btn btn-info">
                                        <i class="fas fa-chart-line"></i> View Forecasting
                                    </a>
                                    <a href="/inventory" class="btn btn-secondary">
                                        <i class="fas fa-boxes"></i> Manage Inventory
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>