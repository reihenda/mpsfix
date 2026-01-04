@extends('layouts.app')

@section('title', 'Test FOB Pricing Form')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Test FOB Pricing Form</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('fob.update-pricing', $customer->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="pricing_date">Periode</label>
                            <input type="month" name="pricing_date" id="pricing_date" class="form-control" 
                                   value="{{ now()->format('Y-m') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="harga_per_meter_kubik">Harga per m³</label>
                            <input type="number" step="0.01" name="harga_per_meter_kubik" id="harga_per_meter_kubik" 
                                   class="form-control" value="{{ $customer->harga_per_meter_kubik ?? 0 }}" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <a href="{{ route('data-pencatatan.fob-detail', $customer->id) }}" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection