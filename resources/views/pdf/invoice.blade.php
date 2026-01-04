<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice - {{ $customer->name }}</title>
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
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
            letter-spacing: 1px;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .logo-section {
            float: left;
            width: 30%;
        }
        
        .company-info {
            float: right;
            width: 70%;
            text-align: right;
            font-size: 12px;
        }
        
        .invoice-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .invoice-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .invoice-details td {
            padding: 5px;
            vertical-align: top;
        }
        
        .customer-box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 10px;
        }
        
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table-items th, .table-items td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .table-items th {
            background-color: #f2f2f2;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .summary-section {
            margin-top: 20px;
            text-align: right;
        }
        
        .total-section {
            margin-top: 15px;
            font-weight: bold;
        }
        
        .bank-info {
            margin-top: 30px;
            padding: 10px;
            border: 1px solid #ccc;
            display: inline-block;
        }
        
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #000;
            width: 80%;
            display: inline-block;
        }
        
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
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
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="no-print print-button">
        <i class="fas fa-print"></i> Cetak Invoice
    </button>
    
    <div class="header-section clearfix">
        <div class="logo-section">
            <img src="{{ asset('img/logo-mps.png') }}" alt="PT MOSAFA PRIMA SINERGI" style="max-width: 150px;">
        </div>
        <div class="company-info">
            <p style="font-weight: bold; font-size: 14px;">PT MOSAFA PRIMA SINERGI</p>
            <p>Gedung Graha Mampang Lt. 2</p>
            <p>Jl. Mampang Prapatan Raya No. 100</p>
            <p>Duren Tiga, Pancoran, Jakarta Selatan 12760</p>
            <p>Tel.021 798 8953</p>
        </div>
    </div>
    
    <div class="invoice-box">
        <div class="invoice-header">
            <h2 style="margin: 0;">INVOICE</h2>
        </div>
        <table class="invoice-details">
            <tr>
                <td width="20%">Nomor</td>
                <td width="1%">:</td>
                <td width="40%">{{ $nomor_invoice }}</td>
                <td width="15%">Kepada</td>
                <td width="1%">:</td>
                <td width="23%"><strong>{{ $customer->name }}</strong></td>
            </tr>
            <tr>
                <td>Tanggal Cetak Tagihan</td>
                <td>:</td>
                <td>{{ $tanggal_cetak }}</td>
                <td>ID Pelanggan</td>
                <td>:</td>
                <td>{{ $id_pelanggan }}</td>
            </tr>
            <tr>
                <td>Tanggal Jatuh Tempo</td>
                <td>:</td>
                <td>{{ $tanggal_jatuh_tempo }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>No Kontrak</td>
                <td>:</td>
                <td>{{ $no_kontrak }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>
    
    <table class="table-items">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="30%">Keterangan</th>
                <th width="25%" class="text-center">Periode</th>
                <th width="15%" class="text-right">Volume Pemakaian (Sm³)</th>
                <th width="10%" class="text-right">Harga Satuan (Rp)</th>
                <th width="15%" class="text-right">Total (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @if(count($pemakaian_gas) > 0)
                @foreach($pemakaian_gas as $index => $item)
                    <tr>
                        <td class="text-center">{{ $item['no'] }}</td>
                        <td>Pemakaian CNG</td>
                        <td class="text-center">{{ $item['periode_pemakaian'] }}</td>
                        <td class="text-right">{{ number_format($item['volume_sm3'], 2) }}</td>
                        <td class="text-right">{{ number_format($item['harga_gas'], 2) }}</td>
                        <td class="text-right">Rp {{ number_format($item['biaya_pemakaian'], 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data pemakaian gas pada periode ini</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">
                    <strong>Terbilang :</strong><br>
                    {{ $terbilang }}
                </td>
                <td class="text-right"><strong>Sub Total</strong></td>
                <td class="text-right" colspan="2"><strong>Rp {{ number_format($total_biaya, 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4" class="text-right"><strong>PPN 11%</strong></td>
                <td class="text-right" colspan="2"><strong>Rp -</strong></td>
            </tr>
            <tr>
                <td colspan="4" class="text-right"><strong>Biaya Materai</strong></td>
                <td class="text-right" colspan="2"><strong>Rp -</strong></td>
            </tr>
            <tr>
                <td colspan="4" class="text-right"><strong>Total Tagihan</strong></td>
                <td class="text-right" colspan="2"><strong>Rp {{ number_format($total_biaya, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="bank-info">
        <strong>Bank Transfer:</strong><br>
        Nama Bank: Bank MANDIRI<br>
        No. Rekening: 006-00-1170431-3<br>
        Atas Nama: PT MOSAFA PRIMA SINERGI
    </div>
    
    <div class="signatures">
        <div class="signature-box"></div>
        <div class="signature-box">
            <p>Finance</p>
            <div class="signature-line"></div>
            <p>Pujianti</p>
        </div>
    </div>

</body>
</html>
