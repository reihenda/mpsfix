<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print - Riwayat Pencatatan FOB {{ $customer->name }}</title>
    <style>
        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #2c3e50;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 15px;  /* Reduced from 20px */
        }

        /* Main Container - Made more compact */
        .print-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);  /* Reduced shadow */
            overflow: hidden;
            position: relative;
        }

        /* Header Section - Made more compact */
        .print-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px 30px 30px;  /* Reduced padding */
            text-align: center;
            position: relative;
        }

        .print-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="2" fill="%23ffffff" fill-opacity="0.03"/><circle cx="80" cy="80" r="2" fill="%23ffffff" fill-opacity="0.03"/><circle cx="40" cy="60" r="1" fill="%23ffffff" fill-opacity="0.02"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>') repeat;
            opacity: 0.1;
        }

        .print-header h1 {
            font-size: 28px;  /* Reduced from 32px */
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;  /* Reduced from 3px */
            margin-bottom: 8px;  /* Reduced from 10px */
            position: relative;
            z-index: 1;
        }

        .print-header h2 {
            font-size: 20px;  /* Reduced from 24px */
            font-weight: 400;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            margin-bottom: 0;
        }

        .header-decoration {
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 15px;  /* Reduced from 20px */
            background: white;
            border-radius: 15px 15px 0 0;
        }

        /* Info Section - Made more compact */
        .print-info {
            padding: 25px 30px;  /* Reduced from 30px */
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;  /* Reduced from 30px */
            align-items: start;
        }

        .info-section {
            background: white;
            padding: 20px;  /* Reduced from 25px */
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .info-section h3 {
            color: #495057;
            font-size: 15px;  /* Reduced from 16px */
            font-weight: 600;
            margin-bottom: 12px;  /* Reduced from 15px */
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;  /* Reduced from 8px */
            border-bottom: 1px solid #f8f9fa;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6c757d;
            font-weight: 500;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 600;
        }

        .highlight-value {
            background: linear-gradient(135deg, #667eea, #764ba2);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            font-size: 16px;
        }

        /* Table Section - Made more compact */
        .table-section {
            padding: 25px 30px;  /* Reduced from 30px */
        }

        .table-header {
            margin-bottom: 15px;  /* Reduced from 20px */
            text-align: center;
        }

        .table-title {
            color: #2c3e50;
            font-size: 18px;  /* Reduced from 20px */
            font-weight: 600;
            margin-bottom: 6px;  /* Reduced from 8px */
        }

        .table-subtitle {
            color: #6c757d;
            font-size: 13px;  /* Reduced from 14px */
        }

        /* Enhanced Table */
        .print-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #dee2e6;
        }

        .print-table th {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            color: white;
            padding: 15px 12px;  /* Reduced from 18px 15px */
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            position: relative;
        }

        .print-table th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .print-table td {
            padding: 12px;  /* Reduced from 15px */
            border: none;
            border-bottom: 1px solid #f8f9fa;
            font-size: 13px;
            vertical-align: middle;
            position: relative;
        }

        .print-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .print-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
        }

        /* Column Alignments */
        .print-table td:first-child {
            text-align: center;
            font-weight: 600;
            color: #495057;
        }

        .print-table td:nth-child(4),
        .print-table td:nth-child(6) {
            text-align: right;
            font-weight: 600;
        }

        .print-table td:nth-child(6) {
            color: #28a745;
            font-family: 'Courier New', monospace;
        }

        /* Footer Section - Made more compact */
        .print-footer {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px 30px;  /* Reduced from 30px */
            border-top: 1px solid #dee2e6;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;  /* Reduced from 30px */
            align-items: center;
        }

        .footer-summary {
            background: white;
            padding: 20px;  /* Reduced from 25px */
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #667eea;
        }

        .summary-title {
            color: #2c3e50;
            font-size: 16px;  /* Reduced from 18px */
            font-weight: 600;
            margin-bottom: 12px;  /* Reduced from 15px */
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-value {
            font-size: 28px;  /* Reduced from 32px */
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .summary-subtitle {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.4;
        }

        .footer-meta {
            text-align: right;
            color: #6c757d;
            font-size: 12px;
            line-height: 1.6;
        }

        .footer-logo {
            color: #495057;
            font-weight: 600;
            margin-top: 15px;
            font-size: 14px;
        }

        /* Print Controls */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .print-controls button {
            margin: 0 5px;
            padding: 10px 18px;
            border: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            cursor: pointer;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .print-controls button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .print-controls button.secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
        }

        .print-controls button.secondary:hover {
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
        }

        /* No Data Message - Made more compact */
        .no-data-message {
            text-align: center;
            padding: 50px 30px;  /* Reduced from 80px 30px */
            margin: 25px 30px;  /* Reduced margin */
            background: white;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
        }

        .no-data-icon {
            font-size: 48px;  /* Reduced from 64px */
            color: #dee2e6;
            margin-bottom: 15px;  /* Reduced from 20px */
        }

        .no-data-title {
            color: #495057;
            font-size: 20px;  /* Reduced from 24px */
            font-weight: 600;
            margin-bottom: 8px;  /* Reduced from 10px */
        }

        .no-data-text {
            color: #6c757d;
            font-size: 16px;
            line-height: 1.5;
        }

        /* Page Break Settings */
        @page {
            size: A4 landscape;
            margin: 15mm;
        }

        /* Print-specific styles */
        @media print {
            body {
                background: white !important;
                padding: 0;
            }
            
            .print-container {
                box-shadow: none;
                border-radius: 0;
                max-width: none;
            }
            
            .print-controls {
                display: none !important;
            }
            
            .print-header {
                background: #667eea !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .print-table th {
                background: #495057 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .info-section,
            .footer-summary {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
            
            .print-table {
                box-shadow: none;
                page-break-inside: avoid;
            }
            
            .highlight-value,
            .summary-value {
                color: #667eea !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .footer-meta {
                text-align: center;
            }
            
            .print-table {
                font-size: 12px;
            }
            
            .print-table th,
            .print-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls">
        <button onclick="window.print()">🖨️ Print</button>
        <button onclick="window.close()" class="secondary">❌ Tutup</button>
        <button onclick="history.back()" class="secondary">← Kembali</button>
    </div>

    <!-- Main Container -->
    <div class="print-container">
        <!-- Header Section -->
        <div class="print-header">
            <h1>Riwayat Pencatatan FOB</h1>
            <h2>{{ $customer->name }}</h2>
            <div class="header-decoration"></div>
        </div>

        <!-- Info Section -->
        <div class="print-info">
            <div class="info-grid">
                <div class="info-section">
                    <h3>📋 Informasi Periode</h3>
                    <div class="info-item">
                        <span class="info-label">Periode</span>
                        <span class="info-value highlight-value">{{ $periode }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Volume</span>
                        <span class="info-value highlight-value">{{ number_format($totalVolume, 2) }} Sm³</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Jumlah Data</span>
                        <span class="info-value">{{ $jumlahData }} transaksi</span>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>🏢 Informasi Customer</h3>
                    <div class="info-item">
                        <span class="info-label">Nomor Kontrak</span>
                        <span class="info-value">{{ $customer->id }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value highlight-value">FOB Active</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tanggal Cetak</span>
                        <span class="info-value">{{ $tanggalCetak }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($dataPencatatan->count() > 0)
            <!-- Table Section -->
            <div class="table-section">
                <div class="table-header">
                    <div class="table-title">Data Riwayat Pencatatan</div>
                    <div class="table-subtitle">Periode {{ $periode }} • {{ $jumlahData }} transaksi</div>
                </div>

                <table class="print-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">No</th>
                            <th style="width: 18%;">Tanggal</th>
                            <th style="width: 12%;">No Pol</th>
                            <th style="width: 15%;">Volume Sm³</th>
                            <th style="width: 25%;">Alamat Pengambilan</th>
                            <th style="width: 22%;">Rupiah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach ($dataPencatatan as $item)
                            @php
                                // PERBAIKAN: Gunakan data dari RekapPengambilan langsung
                                $volumeSm3 = floatval($item->volume);
                                $tanggalItem = \Carbon\Carbon::parse($item->tanggal);
                                $waktuYearMonth = $tanggalItem->format('Y-m');

                                // Ambil pricing info berdasarkan tanggal spesifik
                                $itemPricingInfo = $customer->getPricingForYearMonth(
                                    $waktuYearMonth,
                                    $tanggalItem,
                                );

                                // Hitung Pembelian dengan harga sesuai periode
                                $hargaPerM3 = floatval(
                                    $itemPricingInfo['harga_per_meter_kubik'] ??
                                        $customer->harga_per_meter_kubik,
                                );
                                $pembelian = $volumeSm3 * $hargaPerM3;
                            @endphp
                            <tr>
                                <td>{{ $no++ }}</td>
                                <td>{{ $tanggalItem->format('d M Y H:i') }}</td>
                                <td>{{ $item->nopol ?? '-' }}</td>
                                <td>{{ number_format($volumeSm3, 2) }}</td>
                                <td>{{ $item->alamat_pengambilan ?? '-' }}</td>
                                <td>Rp {{ number_format($pembelian, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- No Data Message -->
            <div class="no-data-message">
                <div class="no-data-icon">📄</div>
                <div class="no-data-title">Tidak Ada Data</div>
                <div class="no-data-text">
                    Tidak ada data pencatatan untuk periode {{ $periode }}<br>
                    Silakan pilih periode lain atau tambah data baru
                </div>
            </div>
        @endif

        <!-- Footer Section -->
        <div class="print-footer">
            <div class="footer-content">
                <div class="footer-summary">
                    <div class="summary-title">
                        📊 Total Volume Periode Ini
                    </div>
                    <div class="summary-value">{{ number_format($totalVolume, 2) }} Sm³</div>
                    <div class="summary-subtitle">
                        Total dari {{ $jumlahData }} data pencatatan<br>
                        untuk periode {{ $periode }}
                    </div>
                </div>
                
                <div class="footer-meta">
                    <div><strong>Sistem Pencatatan FOB</strong></div>
                    <div>Generated on {{ $tanggalCetak }}</div>
                    <div>Customer: {{ $customer->name }}</div>
                    <div>Nomor Kontrak: {{ $customer->id }}</div>
                    <div class="footer-logo">
                        © {{ date('Y') }} Sistem Informasi Perusahaan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Auto print saat halaman dimuat
        window.onload = function() {
            // Delay untuk memastikan halaman ter-render sempurna
            setTimeout(function() {
                window.print();
            }, 800);
        };

        // Handle after print
        window.onafterprint = function() {
            // Optional: tutup window setelah print
            // window.close();
        };

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.close();
            }
            // Ctrl+P untuk manual print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });

        // Smooth entrance animation
        setTimeout(function() {
            document.body.style.opacity = '1';
        }, 100);
    </script>
</body>
</html>
