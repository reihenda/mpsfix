<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Deposit - {{ $customer->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 18px;
            color: #34495e;
            font-weight: normal;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }

        .info-label {
            font-weight: bold;
            color: #2c3e50;
            width: 150px;
        }

        .info-value {
            flex: 1;
            color: #555;
        }

        .summary-box {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 14px;
            color: #2c3e50;
            padding-top: 12px;
        }

        .summary-label {
            font-weight: 600;
            color: #495057;
        }

        .summary-value {
            font-weight: 600;
            text-align: right;
        }

        .summary-value.positive {
            color: #28a745;
        }

        .summary-value.negative {
            color: #dc3545;
        }

        .summary-value.primary {
            color: #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        table thead {
            background: #343a40;
            color: white;
        }

        table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }

        table tbody tr {
            border-bottom: 1px solid #dee2e6;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        table tbody tr:hover {
            background: #e9ecef;
        }

        table td {
            padding: 10px 8px;
            font-size: 11px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .amount-positive {
            color: #28a745;
            font-weight: 600;
        }

        .amount-negative {
            color: #dc3545;
            font-weight: 600;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 10px;
        }

        .print-date {
            text-align: right;
            margin-top: 15px;
            font-size: 10px;
            color: #6c757d;
        }

        /* Print Styles */
        @media print {
            body {
                padding: 0;
            }

            .container {
                max-width: 100%;
            }

            .no-print {
                display: none;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }
        }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .btn-print:hover {
            background: #0056b3;
        }

        @media print {
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print / Save PDF
    </button>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>HISTORY DEPOSIT</h1>
            <h2>{{ $customer->name }}</h2>
        </div>

        <!-- Customer Information -->
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Nama Customer:</span>
                <span class="info-value">{{ $customer->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Periode:</span>
                <span class="info-value">{{ $tanggalMulai }} - {{ $tanggalAkhir }}</span>
            </div>
            @if($customer->alamat)
            <div class="info-row">
                <span class="info-label">Alamat:</span>
                <span class="info-value">{{ $customer->alamat }}</span>
            </div>
            @endif
        </div>

        <!-- Summary Box -->
        <div class="summary-box">
            <div class="summary-row">
                <span class="summary-label">Total Deposit (Keseluruhan):</span>
                <span class="summary-value positive">Rp {{ number_format($totalDepositOverall, 2, ',', '.') }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Total Pembelian (Keseluruhan):</span>
                <span class="summary-value negative">Rp {{ number_format($totalPurchaseOverall, 2, ',', '.') }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Saldo Tersisa:</span>
                <span class="summary-value primary">Rp {{ number_format($saldoTersisa, 2, ',', '.') }}</span>
            </div>
        </div>

        <!-- Deposit History Table -->
        <table>
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="15%">Tanggal</th>
                    <th width="12%" class="text-center">Keterangan</th>
                    <th width="20%" class="text-right">Jumlah</th>
                    <th width="48%">Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $no = 1;
                @endphp
                @forelse($depositHistory as $deposit)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>
                        @if(!empty($deposit['date']))
                            {{ \Carbon\Carbon::parse($deposit['date'])->format('d M Y H:i') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @if($deposit['keterangan'] === 'penambahan')
                            <span class="badge badge-success">Penambahan</span>
                        @else
                            <span class="badge badge-danger">Pengurangan</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($deposit['keterangan'] === 'penambahan')
                            <span class="amount-positive">Rp {{ number_format($deposit['amount'] ?? 0, 2, ',', '.') }}</span>
                        @else
                            <span class="amount-negative">Rp {{ number_format($deposit['amount'] ?? 0, 2, ',', '.') }}</span>
                        @endif
                    </td>
                    <td>{{ $deposit['deskripsi'] ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; color: #6c757d;">
                        Tidak ada data history deposit pada periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p><strong>History Deposit - {{ $customer->name }}</strong></p>
            <p>Dokumen ini dibuat secara otomatis oleh sistem</p>
        </div>

        <div class="print-date">
            Dicetak pada: {{ $tanggalCetak }}
        </div>
    </div>

    <!-- Font Awesome for Print Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>
