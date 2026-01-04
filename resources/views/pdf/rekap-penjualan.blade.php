<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Penjualan Tahun {{ $tahun }}</title>
    <style type="text/css">
        @page {
            size: landscape;
        }
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 20px;
            color: #333;
            background-color: #fff;
            max-width: 100%;
        }
        
        h1 {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
            letter-spacing: 1px;
        }
        
        h2 {
            font-size: 18px;
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 15px;
            color: #2980b9 !important;
            border-bottom: 2px solid #3498db !important;
            padding-bottom: 5px;
            display: inline-block;
        }
        
        .header {
            margin-bottom: 30px;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 20px;
            text-align: center;
        }
        
        .company-info {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .summary-section {
            background-color: #f8f9fa !important;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db !important;
        }
        
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            page-break-inside: auto;
        }
        
        table, th, td {
            border: 1px solid #dee2e6;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #3498db !important;
            color: white !important;
            font-weight: 600;
            font-size: 12px;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #f1f1f1;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-bold {
            font-weight: bold;
        }
        
        .text-customer {
            color: #2980b9;
        }
        
        .text-fob {
            color: #f39c12;
        }
        
        .summary {
            margin-top: 30px;
        }
        
        .total-row {
            font-weight: 700;
            border-top: 2px solid #3498db !important;
            background-color: #f0f8ff !important;
        }
        
        .page-break {
            page-break-after: always;
        }

        .badge {
            display: inline-block;
            padding: 3px 7px;
            font-size: 10px;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 3px;
        }
        
        .badge-primary {
            background-color: #3498db !important;
            color: white !important;
        }
        
        .badge-warning {
            background-color: #f39c12 !important;
            color: white !important;
        }
        
        .print-button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .print-button:hover {
            background-color: #2980b9;
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
            color: #777;
            border-top: 1px solid #eaeaea;
            padding-top: 20px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 20px;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            button.no-print {
                display: none;
            }
            
            .header, .summary-section {
                page-break-inside: avoid;
            }
            
            /* Keep table headers with at least 2 rows */
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            th {
                background-color: #3498db !important;
                color: white !important;
            }
            
            tr:nth-child(even) {
                background-color: #f8f9fa !important;
            }
            
            .total-row {
                background-color: #f0f8ff !important;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN REKAP PENJUALAN TAHUN {{ $tahun }}</h1>
        <h2 style="border-bottom: none; display: block; text-align: center;">PT MOSAFA PRIMA SINERGI</h2>
    </div>

    <button onclick="window.print()" class="no-print print-button">
        <i class="fas fa-print"></i> Cetak Rekap Penjualan
    </button>

    <div class="summary-section" style="text-align: center;">
        <h2 style="display: block; text-align: center; margin-left: auto; margin-right: auto;">Ringkasan Penjualan Tahunan {{ $tahun }}</h2>
        <table class="summary" style="width: 60%; margin: 0 auto;">
            <tr>
                <td width="60%"><strong>Total Volume Pemakaian (Sm³)</strong></td>
                <td class="text-right"><strong>{{ number_format($yearlyData['total']['total_pemakaian'], 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Biaya Pemakaian (Rp)</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($yearlyData['total']['total_pembelian'], 0) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Tanggal Cetak</strong></td>
                <td class="text-right">{{ $currentDate }}</td>
            </tr>
        </table>
    </div>

    <div class="page-break"></div>
    
    <!-- Halaman kedua dimulai di sini -->
    <div style="text-align: center; margin-bottom: 20px;">
        <h2 style="display: block; border-bottom: none; margin-left: auto; margin-right: auto;">PT MOSAFA PRIMA SINERGI</h2>
        <h3 style="display: block; margin-left: auto; margin-right: auto; font-size: 16px; color: #2980b9 !important;">Rekap Penjualan Bulanan Tahun {{ $tahun }}</h3>
    </div>
    <div style="display: flex; justify-content: space-between;">
        <div style="width: 48%;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th class="text-center" width="10%">No</th>
                        <th width="40%">Bulan</th>
                        <th class="text-right" width="50%">Volume (Sm³)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($yearlyData['bulanan'] as $index => $data)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $data['bulan'] }}</td>
                            <td class="text-right">{{ number_format($data['total_pemakaian'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" class="text-right"><strong>Total</strong></td>
                        <td class="text-right"><strong>{{ number_format($yearlyData['total']['total_pemakaian'], 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div style="width: 48%;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th class="text-center" width="10%">No</th>
                        <th width="40%">Bulan</th>
                        <th class="text-right" width="50%">Biaya Pemakaian (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($yearlyData['bulanan'] as $index => $data)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $data['bulan'] }}</td>
                            <td class="text-right">Rp {{ number_format($data['total_pembelian'], 0) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" class="text-right"><strong>Total</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($yearlyData['total']['total_pembelian'], 0) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-break"></div>

    <h2>Rekap Penjualan Per Customer Tahun {{ $tahun }}</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="30%">Nama Customer</th>
                <th width="5%">Tipe</th>
                <th class="text-right" width="25%">Total Volume (Sm³)</th>
                <th class="text-right" width="35%">Total Biaya (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customersData as $index => $customer)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $customer['nama'] }}</td>
                    <td class="text-center">
                        @if($customer['role'] == 'customer')
                            <span class="badge badge-primary">C</span>
                        @else
                            <span class="badge badge-warning">F</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($customer['pemakaian_tahun'], 2) }}</td>
                    <td class="text-right">Rp {{ number_format($customer['pembelian_tahun'], 0) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($yearlyData['total']['total_pemakaian'], 2) }}</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($yearlyData['total']['total_pembelian'], 0) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>Detail Penjualan Per Bulan Per Customer Tahun {{ $tahun }}</h2>
    <table style="font-size: 10px;">
        <thead>
            <tr>
                <th rowspan="2" class="text-center" style="vertical-align: middle; width: 3%;">No</th>
                <th rowspan="2" style="vertical-align: middle; width: 15%;">Customer</th>
                <th colspan="12" class="text-center">Volume Pemakaian (Sm³)</th>
                <th rowspan="2" class="text-center" style="vertical-align: middle; width: 6%;">Total</th>
            </tr>
            <tr>
                <th class="text-center" style="width: 5%;">Jan</th>
                <th class="text-center" style="width: 5%;">Feb</th>
                <th class="text-center" style="width: 5%;">Mar</th>
                <th class="text-center" style="width: 5%;">Apr</th>
                <th class="text-center" style="width: 5%;">Mei</th>
                <th class="text-center" style="width: 5%;">Jun</th>
                <th class="text-center" style="width: 5%;">Jul</th>
                <th class="text-center" style="width: 5%;">Agu</th>
                <th class="text-center" style="width: 5%;">Sep</th>
                <th class="text-center" style="width: 5%;">Okt</th>
                <th class="text-center" style="width: 5%;">Nov</th>
                <th class="text-center" style="width: 5%;">Des</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customersData as $index => $customer)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $customer['nama'] }}
                        @if($customer['role'] == 'customer')
                            <span class="badge badge-primary">C</span>
                        @else
                            <span class="badge badge-warning">F</span>
                        @endif
                    </td>
                    @for($bulan = 1; $bulan <= 12; $bulan++)
                        <td class="text-right">{{ number_format($customer['pemakaian_bulan'][$bulan] ?? 0, 1) }}</td>
                    @endfor
                    <td class="text-right text-bold">{{ number_format($customer['pemakaian_tahun'], 1) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>TOTAL</strong></td>
                @php
                    $totalPerBulan = [];
                    for ($bulan = 1; $bulan <= 12; $bulan++) {
                        $totalPerBulan[$bulan] = 0;
                        foreach ($customersData as $customer) {
                            $totalPerBulan[$bulan] += ($customer['pemakaian_bulan'][$bulan] ?? 0);
                        }
                    }
                @endphp
                @for($bulan = 1; $bulan <= 12; $bulan++)
                    <td class="text-right"><strong>{{ number_format($totalPerBulan[$bulan], 1) }}</strong></td>
                @endfor
                <td class="text-right text-bold">{{ number_format($yearlyData['total']['total_pemakaian'], 1) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>Detail Biaya Pemakaian Per Bulan Per Customer Tahun {{ $tahun }}</h2>
    <table style="font-size: 10px;">
        <thead>
            <tr>
                <th rowspan="2" class="text-center" style="vertical-align: middle; width: 3%;">No</th>
                <th rowspan="2" style="vertical-align: middle; width: 15%;">Customer</th>
                <th colspan="12" class="text-center">Biaya Pemakaian (Rp)</th>
                <th rowspan="2" class="text-center" style="vertical-align: middle; width: 6%;">Total</th>
            </tr>
            <tr>
                <th class="text-center" style="width: 5%;">Jan</th>
                <th class="text-center" style="width: 5%;">Feb</th>
                <th class="text-center" style="width: 5%;">Mar</th>
                <th class="text-center" style="width: 5%;">Apr</th>
                <th class="text-center" style="width: 5%;">Mei</th>
                <th class="text-center" style="width: 5%;">Jun</th>
                <th class="text-center" style="width: 5%;">Jul</th>
                <th class="text-center" style="width: 5%;">Agu</th>
                <th class="text-center" style="width: 5%;">Sep</th>
                <th class="text-center" style="width: 5%;">Okt</th>
                <th class="text-center" style="width: 5%;">Nov</th>
                <th class="text-center" style="width: 5%;">Des</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customersData as $index => $customer)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $customer['nama'] }}
                        @if($customer['role'] == 'customer')
                            <span class="badge badge-primary">C</span>
                        @else
                            <span class="badge badge-warning">F</span>
                        @endif
                    </td>
                    @for($bulan = 1; $bulan <= 12; $bulan++)
                        <td class="text-right">{{ number_format($customer['pembelian_bulan'][$bulan] ?? 0, 0) }}</td>
                    @endfor
                    <td class="text-right text-bold">{{ number_format($customer['pembelian_tahun'], 0) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>TOTAL</strong></td>
                @php
                    $totalPerBulan = [];
                    for ($bulan = 1; $bulan <= 12; $bulan++) {
                        $totalPerBulan[$bulan] = 0;
                        foreach ($customersData as $customer) {
                            $totalPerBulan[$bulan] += ($customer['pembelian_bulan'][$bulan] ?? 0);
                        }
                    }
                @endphp
                @for($bulan = 1; $bulan <= 12; $bulan++)
                    <td class="text-right"><strong>{{ number_format($totalPerBulan[$bulan], 0) }}</strong></td>
                @endfor
                <td class="text-right text-bold">{{ number_format($yearlyData['total']['total_pembelian'], 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p><strong>PT MOSAFA PRIMA SINERGI</strong></p>
        <p>Dokumen ini dicetak pada {{ $currentDate }}</p>
        <p style="font-size: 10px; color: #666;">Halaman 1</p>
    </div>
</body>
</html>
