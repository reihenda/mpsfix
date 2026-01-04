<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Billing - {{ $customer->name }}</title>
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 30px;
            color: #333;
            background-color: #fff;
        }
        
        h1 {
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
            letter-spacing: 1px;
        }
        
        h2 {
            font-size: 16px;
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 15px;
            color: #2980b9;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            display: inline-block;
        }
        
        .header {
            margin-bottom: 30px;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 20px;
        }
        
        .company-info {
            float: left;
            width: 60%;
        }
        
        .billing-info {
            float: right;
            width: 40%;
            text-align: right;
        }
        
        .customer-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
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
        }
        
        table, th, td {
            border: 1px solid #dee2e6;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
        }
        
        th {
            background-color: #3498db;
            color: white;
            font-weight: 600;
            font-size: 13px;
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
        
        .summary {
            margin-top: 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2ecc71;
        }
        
        .summary table {
            width: 70%;
            margin-left: auto;
            margin-right: 0;
            box-shadow: none;
        }
        
        .summary table, .summary th, .summary td {
            border: none;
        }
        
        .summary td {
            padding: 8px;
            border-bottom: 1px dashed #eaeaea;
        }
        
        .summary td:first-child {
            width: 70%;
            color: #555;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
            color: #777;
            border-top: 1px solid #eaeaea;
            padding-top: 20px;
        }
        
        .total-row {
            font-weight: 700;
            border-top: 2px solid #3498db;
            background-color: #f0f8ff !important;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .must-pay {
            background-color: #e74c3c;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 600;
            margin-top: 10px;
            display: inline-block;
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
        
        @media print {
            body {
                margin: 0;
                padding: 20px;
            }
            
            button.no-print {
                display: none;
            }
            
            .header, .summary {
                page-break-inside: avoid;
            }
            
            /* Allow tables to break across pages */
            table {
                page-break-inside: auto;
            }
            
            /* Keep table headers with at least 2 rows */
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>
    <div class="header clearfix">
        <h1 style="text-align: center; width: 100%;">BILLING PEMAKAIAN GAS</h1>
    </div>

    <div class="customer-details">
        <p><strong>Nama Customer:</strong> {{ $customer->name }}</p>
        <p><strong>Periode Tagihan:</strong> {{ $periode_bulan }}</p>
        <p><strong>No. Pelanggan:</strong> {{ $customer->id ?? 'N/A' }}</p>
    </div>

    <button onclick="window.print()" class="no-print print-button">
        <i class="fas fa-print"></i> Cetak Billing
    </button>

    <h2>Rincian Pemakaian Gas</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="20%">Tanggal Pencatatan</th>
                <th class="text-right" width="25%">Volume (Sm³)</th>
                <th class="text-right" width="25%">Harga Gas</th>
                <th class="text-right" width="25%">Biaya Pemakaian Gas</th>
            </tr>
        </thead>
        <tbody>
            @if(count($pemakaian_gas) > 0)
                @foreach($pemakaian_gas as $item)
                    <tr>
                        <td class="text-center">{{ $item['no'] }}</td>
                        <td>{{ $item['periode_pemakaian'] }}</td>
                        <td class="text-right">{{ number_format($item['volume_sm3'], 2) }}</td>
                        <td class="text-right">Rp {{ number_format($item['harga_gas'], 2) }}</td>
                        <td class="text-right">Rp {{ number_format($item['biaya_pemakaian'], 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data pemakaian gas pada periode ini</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ number_format($total_volume, 2) }}</strong></td>
                <td></td>
                <td class="text-right"><strong>Rp {{ number_format($total_biaya, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <h2>Rincian Penerimaan Deposit</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center" width="10%">No</th>
                <th width="50%">Tanggal Deposit</th>
                <th class="text-right" width="40%">Jumlah Penerimaan</th>
            </tr>
        </thead>
        <tbody>
            @if(count($penerimaan_deposit) > 0)
                @foreach($penerimaan_deposit as $item)
                    <tr>
                        <td class="text-center">{{ $item['no'] }}</td>
                        <td>{{ $item['tanggal_deposit'] }}</td>
                        <td class="text-right">Rp {{ number_format($item['jumlah_penerimaan'], 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="3" class="text-center">Tidak ada penerimaan deposit pada periode ini</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>Total</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($total_deposit, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <h2>Ringkasan Tagihan</h2>
    <div class="summary">
        <table>
            <tr>
                <td>1. Biaya Pemakaian Gas</td>
                <td class="text-right">Rp {{ number_format($total_biaya, 2) }}</td>
            </tr>
            <tr>
                <td>2. Penerimaan Deposit Bulan Ini</td>
                <td class="text-right">Rp {{ number_format($total_deposit, 2) }}</td>
            </tr>
            <tr>
                <td>3. Saldo Bulan Lalu</td>
                <td class="text-right">Rp {{ number_format($saldo_bulan_lalu, 2) }}</td>
            </tr>
            <tr>
                <td>4. Sisa Saldo (2 + 3 - 1)</td>
                <td class="text-right">Rp {{ number_format($sisa_saldo, 2) }}</td>
            </tr>
            @if($biaya_yang_harus_dibayar > 0)
            <tr>
                <td><strong>5. Jumlah Yang Harus Dibayar</strong></td>
                <td class="text-right"><span class="must-pay">Rp {{ number_format($biaya_yang_harus_dibayar, 2) }}</span></td>
            </tr>
            @else
            <tr>
                <td><strong>5. Jumlah Yang Harus Dibayar</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($biaya_yang_harus_dibayar, 2) }}</strong></td>
            </tr>
            @endif
        </table>
    </div>

    <div class="footer">
        <p><strong>Terima kasih atas kepercayaan Anda menggunakan layanan kami.</strong></p>
        <p>Dokumen ini dicetak pada {{ now()->format('d F Y H:i') }}</p>
        <p>Untuk pertanyaan terkait tagihan, silakan hubungi tim layanan pelanggan.</p>
    </div>
</body>
</html>
