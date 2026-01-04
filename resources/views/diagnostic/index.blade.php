@extends('layouts.app')

@section('title', 'Diagnostik Sistem')

@section('page-title', 'Diagnostik Sistem')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h4>Alat Diagnostik dan Troubleshooting</h4>
                    <p>Halaman ini menyediakan informasi diagnostik untuk membantu mengatasi masalah teknis.</p>
                    
                    @if(session('results'))
                    <div class="alert alert-info">
                        <h5>Hasil Operasi Clear Cache:</h5>
                        <ul>
                            @foreach(session('results') as $key => $message)
                                <li><strong>{{ str_replace('_', ' ', ucfirst($key)) }}:</strong> {{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    <div class="btn-group mb-3">
                        <a href="{{ route('diagnostic.clear-cache') }}" class="btn btn-warning">
                            <i class="fas fa-broom mr-1"></i> Clear Cache
                        </a>
                        <button id="testDatabase" class="btn btn-info">
                            <i class="fas fa-database mr-1"></i> Test Database
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Database Test Results -->
    <div id="dbTestResults" style="display:none;" class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database mr-2"></i>
                        Database Test Results
                    </h5>
                </div>
                <div class="card-body">
                    <div id="dbTestContent">
                        <!-- Filled via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Environment Info -->
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-server mr-2"></i>
                        Environment Info
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tr>
                            <th>Environment</th>
                            <td>{{ $info['environment'] }}</td>
                        </tr>
                        <tr>
                            <th>PHP Version</th>
                            <td>{{ $info['php_version'] }}</td>
                        </tr>
                        <tr>
                            <th>Laravel Version</th>
                            <td>{{ $info['laravel_version'] }}</td>
                        </tr>
                        <tr>
                            <th>Debug Mode</th>
                            <td>
                                @if($info['is_debug'])
                                    <span class="badge badge-success">Enabled</span>
                                @else
                                    <span class="badge badge-warning">Disabled</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Database Connection</th>
                            <td>{{ $info['database_connection'] }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Routes Info -->
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-route mr-2"></i>
                        Ukuran Related Routes
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($info['routes']) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>URI</th>
                                        <th>Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($info['routes'] as $route)
                                        <tr>
                                            <td>{{ implode('|', $route['methods']) }}</td>
                                            <td>{{ $route['uri'] }}</td>
                                            <td>{{ $route['name'] ?? '-' }}</td>
                                            <td>{{ $route['action'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            No related routes found.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Tables Info -->
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table mr-2"></i>
                        Database Tables
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($info['db_error']))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Database Error: {{ $info['db_error'] }}
                        </div>
                    @elseif(count($info['tables']) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Table Name</th>
                                        <th>Row Count</th>
                                        <th>Columns</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($info['tables'] as $name => $table)
                                        <tr class="{{ $name == 'ukuran_truk' ? 'table-primary' : '' }}">
                                            <td>{{ $name }}</td>
                                            <td>{{ $table['count'] }}</td>
                                            <td>
                                                <small>{{ implode(', ', $table['columns']) }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            No tables found or could not access database tables.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Ukuran Data -->
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-ruler mr-2"></i>
                        Ukuran Data (ukuran_truk table)
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($info['ukuran_data']) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Ukuran</th>
                                        <th>Created At</th>
                                        <th>Updated At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($info['ukuran_data'] as $item)
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td>{{ $item->ukuran }}</td>
                                            <td>{{ $item->created_at }}</td>
                                            <td>{{ $item->updated_at }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            No data found in ukuran_truk table. 
                            <a href="{{ route('debug.ukuran') }}" class="btn btn-sm btn-primary ml-2">
                                View Detailed Ukuran Debug
                            </a>
                        </div>
                        
                        <div class="card bg-light mt-3">
                            <div class="card-body">
                                <h5>Kemungkinan Solusi:</h5>
                                <ol>
                                    <li>Pastikan tabel <code>ukuran_truk</code> sudah dibuat di database</li>
                                    <li>Tambahkan data ukuran secara manual melalui SQL:</li>
                                </ol>
                                
                                <div class="bg-dark text-white p-3 rounded">
                                    <pre>INSERT INTO ukuran_truk (ukuran, created_at, updated_at) 
VALUES 
('20 feet', NOW(), NOW()),
('40 feet', NOW(), NOW()),
('53 feet', NOW(), NOW());</pre>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#testDatabase').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Testing...');
        
        $.ajax({
            url: '{{ route("diagnostic.test-db") }}',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#testDatabase').prop('disabled', false).html('<i class="fas fa-database mr-1"></i> Test Database');
                
                // Prepare results HTML
                let html = '';
                if (response.success) {
                    html += '<div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>' + response.message + '</div>';
                    
                    // Show tables info
                    if (Object.keys(response.tables).length > 0) {
                        html += '<h5>Tables Information:</h5>';
                        html += '<div class="table-responsive"><table class="table table-sm table-striped">';
                        html += '<thead><tr><th>Table</th><th>Rows</th><th>Columns</th></tr></thead><tbody>';
                        
                        for (const [tableName, tableInfo] of Object.entries(response.tables)) {
                            const highlightClass = tableName === 'ukuran_truk' ? 'class="table-primary"' : '';
                            html += '<tr ' + highlightClass + '>';
                            html += '<td>' + tableName + '</td>';
                            html += '<td>' + tableInfo.count + '</td>';
                            html += '<td><small>' + tableInfo.columns.join(', ') + '</small></td>';
                            html += '</tr>';
                        }
                        
                        html += '</tbody></table></div>';
                    }
                } else {
                    html += '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>' + response.message + '</div>';
                }
                
                // Display results
                $('#dbTestContent').html(html);
                $('#dbTestResults').show();
            },
            error: function(xhr, status, error) {
                $('#testDatabase').prop('disabled', false).html('<i class="fas fa-database mr-1"></i> Test Database');
                
                // Show error message
                const html = '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-triangle mr-2"></i>' +
                    'Error testing database: ' + error +
                    '</div>';
                
                $('#dbTestContent').html(html);
                $('#dbTestResults').show();
            }
        });
    });
});
</script>
@endpush
