@extends('layouts.app')

@section('title', 'FOB Debug')

@section('page-title', 'FOB Debug Page')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">FOB Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Basic Info</h4>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>ID</th>
                                        <td>{{ $fob->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td>{{ $fob->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td>{{ $fob->email }}</td>
                                    </tr>
                                    <tr>
                                        <th>Role</th>
                                        <td>{{ $fob->role }}</td>
                                    </tr>
                                    <tr>
                                        <th>Is FOB?</th>
                                        <td>{{ $fob->isFOB() ? 'Yes' : 'No' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Current Price per m³</th>
                                        <td>Rp {{ number_format($fob->harga_per_meter_kubik, 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4>Pricing History</h4>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Price per m³</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $pricingHistory = is_string($fob->pricing_history) 
                                                ? json_decode($fob->pricing_history, true) ?? [] 
                                                : ($fob->pricing_history ?? []);
                                        @endphp
                                        
                                        @if(count($pricingHistory) > 0)
                                            @foreach($pricingHistory as $pricing)
                                                <tr>
                                                    <td>{{ isset($pricing['date']) ? date('F Y', strtotime($pricing['date'])) : 'N/A' }}</td>
                                                    <td>Rp {{ number_format($pricing['harga_per_meter_kubik'] ?? 0, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="2" class="text-center">No pricing history available</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">Test Update FOB Pricing</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('fob.update-pricing', $fob->id) }}" method="POST" id="testPricingForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pricing_date">Pricing Period</label>
                                        <input type="month" name="pricing_date" id="pricing_date" class="form-control" value="{{ now()->format('Y-m') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="harga_per_meter_kubik">Price per m³</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" step="0.01" name="harga_per_meter_kubik" id="harga_per_meter_kubik" class="form-control" value="{{ $fob->harga_per_meter_kubik }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Update Pricing</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title">Debug Information</h3>
                    </div>
                    <div class="card-body">
                        <h4>Route Information</h4>
                        <p>FOB Detail Route: <code>{{ route('data-pencatatan.fob-detail', $fob->id) }}</code></p>
                        <p>FOB Update Pricing Route: <code>{{ route('fob.update-pricing', $fob->id) }}</code></p>
                        
                        <h4>Session/Flash Messages</h4>
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    $(function() {
        // Add form submit handler
        $("#testPricingForm").on("submit", function(e) {
            console.log("Debug form submitted");
            
            // Log form data
            var formData = $(this).serialize();
            console.log("Form data:", formData);
        });
    });
</script>
@endsection
