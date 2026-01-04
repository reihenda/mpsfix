@extends('layouts.app')

@section('title', 'Real-time Balance System Test')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🧪 Real-time Balance System Test</h3>
                </div>
                <div class="card-body">
                    
                    <!-- System Status -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4>📊 System Status</h4>
                            <div id="system-status">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p>Loading system status...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Balance Test -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4>💰 Customer Balance Test</h4>
                            <div class="form-group">
                                <label>Customer ID:</label>
                                <input type="number" id="customer-id" class="form-control" placeholder="Enter customer ID">
                            </div>
                            <div class="form-group">
                                <label>Year-Month:</label>
                                <input type="month" id="year-month" class="form-control" value="{{ now()->format('Y-m') }}">
                            </div>
                            <button class="btn btn-primary" onclick="testCustomerBalance()">
                                Test Customer Balance
                            </button>
                            <div id="customer-balance-result" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Comparison Report -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4>📈 Comparison Report</h4>
                            <button class="btn btn-info" onclick="loadComparisonReport()">
                                Load Comparison Report
                            </button>
                            <div id="comparison-report" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Dashboard Data -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4>📋 Dashboard Data</h4>
                            <div class="form-group">
                                <label>Year:</label>
                                <select id="dashboard-year" class="form-control">
                                    @for($year = now()->year; $year >= 2020; $year--)
                                        <option value="{{ $year }}" {{ $year == now()->year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <button class="btn btn-success" onclick="loadDashboardData()">
                                Load Dashboard Data
                            </button>
                            <div id="dashboard-data" class="mt-3"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadSystemStatus();
});

async function loadSystemStatus() {
    try {
        const response = await fetch('/api/realtime-balance/status');
        const data = await response.json();
        
        let html = '';
        if (data.status === 'operational') {
            html = `
                <div class="alert alert-success">
                    <h5>✅ System Operational</h5>
                    <ul>
                        <li>Monthly Balances: <strong>${data.statistics.total_monthly_balances.toLocaleString()}</strong> records</li>
                        <li>Transaction Calculations: <strong>${data.statistics.total_transaction_calculations.toLocaleString()}</strong> records</li>
                        <li>Active Customers: <strong>${data.statistics.active_customers}</strong></li>
                        <li>Avg Query Time: <strong>${data.statistics.avg_query_time_ms}ms</strong></li>
                    </ul>
                </div>
            `;
            
            if (data.recent_updates && data.recent_updates.length > 0) {
                html += `
                    <h6>Recent Updates:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Period</th>
                                    <th>Balance</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.recent_updates.forEach(update => {
                    html += `
                        <tr>
                            <td>${update.customer_name}</td>
                            <td>${update.year_month}</td>
                            <td>Rp ${parseFloat(update.closing_balance).toLocaleString()}</td>
                            <td>${new Date(update.last_calculated_at).toLocaleString()}</td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
        } else {
            html = '<div class="alert alert-danger">❌ System Error</div>';
        }
        
        document.getElementById('system-status').innerHTML = html;
    } catch (error) {
        document.getElementById('system-status').innerHTML = 
            `<div class="alert alert-danger">❌ Error loading system status: ${error.message}</div>`;
    }
}

async function testCustomerBalance() {
    const customerId = document.getElementById('customer-id').value;
    const yearMonth = document.getElementById('year-month').value;
    
    if (!customerId) {
        alert('Please enter customer ID');
        return;
    }
    
    const resultDiv = document.getElementById('customer-balance-result');
    resultDiv.innerHTML = '<div class="text-center"><div class="spinner-border"></div> Loading...</div>';
    
    try {
        // Load balance
        const balanceResponse = await fetch(`/api/realtime-balance/customer/${customerId}/balance?year_month=${yearMonth}`);
        const balanceData = await balanceResponse.json();
        
        // Load transactions
        const transactionsResponse = await fetch(`/api/realtime-balance/customer/${customerId}/transactions?year_month=${yearMonth}`);
        const transactionsData = await transactionsResponse.json();
        
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>💰 Balance Data</h5>
                        </div>
                        <div class="card-body">
        `;
        
        if (balanceResponse.ok) {
            const balance = balanceData.balance;
            html += `
                <p><strong>Customer:</strong> ${balanceData.customer_name}</p>
                <p><strong>Period:</strong> ${balanceData.year_month}</p>
                <hr>
                <p>Opening Balance: <strong>Rp ${parseFloat(balance.opening_balance).toLocaleString()}</strong></p>
                <p>Total Deposits: <strong>Rp ${parseFloat(balance.total_deposits).toLocaleString()}</strong></p>
                <p>Total Purchases: <strong>Rp ${parseFloat(balance.total_purchases).toLocaleString()}</strong></p>
                <p>Closing Balance: <strong>Rp ${parseFloat(balance.closing_balance).toLocaleString()}</strong></p>
                <p>Total Volume: <strong>${parseFloat(balance.total_volume_sm3).toLocaleString()} Sm³</strong></p>
                <p><small>Last Updated: ${new Date(balance.last_calculated_at).toLocaleString()}</small></p>
            `;
        } else {
            html += `<div class="alert alert-warning">${balanceData.message || 'Balance data not found'}</div>`;
        }
        
        html += `
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>📋 Transactions</h5>
                        </div>
                        <div class="card-body">
        `;
        
        if (transactionsResponse.ok && transactionsData.transactions.length > 0) {
            html += `
                <p><strong>Summary:</strong></p>
                <ul>
                    <li>Total Transactions: ${transactionsData.summary.total_transactions}</li>
                    <li>Total Volume: ${parseFloat(transactionsData.summary.total_volume).toLocaleString()} Sm³</li>
                    <li>Total Amount: Rp ${parseFloat(transactionsData.summary.total_amount).toLocaleString()}</li>
                </ul>
                <hr>
                <div style="max-height: 200px; overflow-y: auto;">
            `;
            
            transactionsData.transactions.forEach(transaction => {
                html += `
                    <div class="mb-2 p-2 border-bottom">
                        <strong>${transaction.transaction_date}</strong><br>
                        Volume: ${parseFloat(transaction.volume_sm3).toFixed(2)} Sm³<br>
                        Amount: Rp ${parseFloat(transaction.total_harga).toLocaleString()}<br>
                        <small>Rate: Rp ${parseFloat(transaction.harga_per_m3).toLocaleString()}/m³</small>
                    </div>
                `;
            });
            
            html += '</div>';
        } else {
            html += '<div class="alert alert-info">No transactions found for this period</div>';
        }
        
        html += `
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        resultDiv.innerHTML = html;
    } catch (error) {
        resultDiv.innerHTML = `<div class="alert alert-danger">❌ Error: ${error.message}</div>`;
    }
}

async function loadComparisonReport() {
    const resultDiv = document.getElementById('comparison-report');
    resultDiv.innerHTML = '<div class="text-center"><div class="spinner-border"></div> Loading...</div>';
    
    try {
        const response = await fetch('/api/realtime-balance/comparison-report');
        const data = await response.json();
        
        let html = `
            <div class="alert alert-info">
                <strong>Summary:</strong> 
                ${data.summary.matches}/${data.summary.total_checked} customers match. 
                Max difference: Rp ${parseFloat(data.summary.max_difference).toLocaleString()}
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Database Balance</th>
                            <th>Old Calculation</th>
                            <th>Difference</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        data.comparison.forEach(item => {
            const statusClass = item.match ? 'success' : 'warning';
            const statusIcon = item.match ? '✅' : '⚠️';
            
            html += `
                <tr>
                    <td>${item.customer_name}</td>
                    <td>Rp ${parseFloat(item.database_balance).toLocaleString()}</td>
                    <td>Rp ${parseFloat(item.old_calculation_balance).toLocaleString()}</td>
                    <td>Rp ${parseFloat(item.difference).toLocaleString()}</td>
                    <td><span class="badge badge-${statusClass}">${statusIcon} ${item.match ? 'Match' : 'Diff'}</span></td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        resultDiv.innerHTML = html;
    } catch (error) {
        resultDiv.innerHTML = `<div class="alert alert-danger">❌ Error: ${error.message}</div>`;
    }
}

async function loadDashboardData() {
    const year = document.getElementById('dashboard-year').value;
    const resultDiv = document.getElementById('dashboard-data');
    resultDiv.innerHTML = '<div class="text-center"><div class="spinner-border"></div> Loading...</div>';
    
    try {
        const response = await fetch(`/api/realtime-balance/dashboard?year=${year}`);
        const data = await response.json();
        
        let html = `
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h6>📊 Summary ${data.year}</h6></div>
                        <div class="card-body">
                            <p>Total Customers: <strong>${data.summary.total_customers}</strong></p>
                            <p>Total Deposits: <strong>Rp ${parseFloat(data.summary.total_deposits || 0).toLocaleString()}</strong></p>
                            <p>Total Purchases: <strong>Rp ${parseFloat(data.summary.total_purchases || 0).toLocaleString()}</strong></p>
                            <p>Total Volume: <strong>${parseFloat(data.summary.total_volume || 0).toLocaleString()} Sm³</strong></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h6>📈 Monthly Trend</h6></div>
                        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Deposits</th>
                                            <th>Purchases</th>
                                            <th>Volume</th>
                                        </tr>
                                    </thead>
                                    <tbody>
        `;
        
        data.monthly_trend.forEach(month => {
            html += `
                <tr>
                    <td>${month.year_month}</td>
                    <td>Rp ${parseFloat(month.monthly_deposits || 0).toLocaleString()}</td>
                    <td>Rp ${parseFloat(month.monthly_purchases || 0).toLocaleString()}</td>
                    <td>${parseFloat(month.monthly_volume || 0).toLocaleString()} Sm³</td>
                </tr>
            `;
        });
        
        html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        if (data.top_customers && data.top_customers.length > 0) {
            html += `
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><h6>🏆 Top Customers by Volume</h6></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Customer</th>
                                                <th>Total Volume</th>
                                                <th>Total Purchases</th>
                                                <th>Avg Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
            `;
            
            data.top_customers.forEach((customer, index) => {
                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${customer.name}</td>
                        <td>${parseFloat(customer.total_volume || 0).toLocaleString()} Sm³</td>
                        <td>Rp ${parseFloat(customer.total_purchases || 0).toLocaleString()}</td>
                        <td>Rp ${parseFloat(customer.avg_balance || 0).toLocaleString()}</td>
                    </tr>
                `;
            });
            
            html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        resultDiv.innerHTML = html;
    } catch (error) {
        resultDiv.innerHTML = `<div class="alert alert-danger">❌ Error: ${error.message}</div>`;
    }
}
</script>

@endsection
