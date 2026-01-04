// Method untuk mencetak invoice dalam bentuk HTML (dapat diprint oleh browser)
public function printInvoice(Request $request, User $customer)
{
    // Get filter parameters
    $bulan = $request->input('bulan', now()->month);
    $tahun = $request->input('tahun', now()->year);

    // Format filter untuk query
    $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

    // Get pricing info for selected month
    $pricingInfo = $customer->getPricingForYearMonth($yearMonth);

    // Base query
    $query = $customer->dataPencatatan();

    // Ambil semua data dulu
    $dataPencatatan = $query->get();

    // Filter data berdasarkan bulan dan tahun dari pembacaan awal
    $dataPencatatan = $dataPencatatan->filter(function ($item) use ($yearMonth) {
        $dataInput = $this->ensureArray($item->data_input);

        // Jika data input kosong atau tidak ada waktu awal, skip
        if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
            return false;
        }

        // Convert the timestamp to year-month format for comparison
        $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');

        // Filter by year-month
        return $waktuAwal === $yearMonth;
    });

    // Perhitungan untuk volume dan biaya pemakaian gas
    $pemakaianGas = [];
    $totalVolume = 0;
    $totalBiaya = 0;
    $hargaGas = 0;

    foreach ($dataPencatatan as $item) {
        $dataInput = $this->ensureArray($item->data_input);
        $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
        $volumeSm3 = $volumeFlowMeter * floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
        // Use the last price as the standard price
        $hargaGas = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
        
        $totalVolume += $volumeSm3;
    }
    
    // Calculate total cost with total volume
    $totalBiaya = $totalVolume * $hargaGas;
    
    // Create a single row with month's total
    // Format periode pemakaian for the entire month
    $periodePemakaian = Carbon::createFromDate($tahun, $bulan, 1)->format('1 F Y') . " - " . 
                     Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth()->format('d F Y');
    
    $pemakaianGas[] = [
        'no' => 1,
        'periode_pemakaian' => $periodePemakaian,
        'volume_sm3' => $totalVolume,
        'harga_gas' => $hargaGas,
        'biaya_pemakaian' => $totalBiaya
    ];

    // Generate nomor invoice
    $nomorInvoice = sprintf('%03d/MPS/INV-NOMI/II/%s', $customer->id, date('Y'));
    
    // Generate tanggal jatuh tempo (10 hari dari tanggal cetak)
    $tanggalCetak = Carbon::now()->format('d-M-Y');
    $tanggalJatuhTempo = Carbon::now()->addDays(10)->format('d-M-Y');
    
    // Generate nomor kontrak
    $noKontrak = sprintf('001/PJBG-MPS/I/%s', date('Y'));
    
    // Generate ID Pelanggan (contoh format)
    $idPelanggan = sprintf('03C%04d', $customer->id);
    
    // Terbilang untuk total tagihan
    $terbilang = $this->terbilang($totalBiaya);

    // Setup data untuk HTML Invoice
    $data = [
        'customer' => $customer,
        'periode_bulan' => Carbon::createFromDate($tahun, $bulan, 1)->format('F Y'),
        'pemakaian_gas' => $pemakaianGas,
        'total_volume' => $totalVolume,
        'total_biaya' => $totalBiaya,
        'nomor_invoice' => $nomorInvoice,
        'tanggal_cetak' => $tanggalCetak,
        'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
        'no_kontrak' => $noKontrak,
        'id_pelanggan' => $idPelanggan,
        'terbilang' => $terbilang
    ];

    // Return view HTML yang dapat dicetak
    return view('pdf.invoice', $data);
}

// Helper function untuk mengubah angka menjadi kata-kata dalam bahasa Indonesia
private function terbilang($angka) {
    $angka = abs($angka);
    $baca = array('', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas');
    $terbilang = '';
    
    if ($angka < 12) {
        $terbilang = ' ' . $baca[$angka];
    } elseif ($angka < 20) {
        $terbilang = $this->terbilang($angka - 10) . ' belas';
    } elseif ($angka < 100) {
        $terbilang = $this->terbilang((int)($angka / 10)) . ' puluh' . $this->terbilang($angka % 10);
    } elseif ($angka < 200) {
        $terbilang = ' seratus' . $this->terbilang($angka - 100);
    } elseif ($angka < 1000) {
        $terbilang = $this->terbilang((int)($angka / 100)) . ' ratus' . $this->terbilang($angka % 100);
    } elseif ($angka < 2000) {
        $terbilang = ' seribu' . $this->terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        $terbilang = $this->terbilang((int)($angka / 1000)) . ' ribu' . $this->terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        $terbilang = $this->terbilang((int)($angka / 1000000)) . ' juta' . $this->terbilang($angka % 1000000);
    } elseif ($angka < 1000000000000) {
        $terbilang = $this->terbilang((int)($angka / 1000000000)) . ' milyar' . $this->terbilang($angka % 1000000000);
    } elseif ($angka < 1000000000000000) {
        $terbilang = $this->terbilang((int)($angka / 1000000000000)) . ' trilyun' . $this->terbilang($angka % 1000000000000);
    }
    
    return $terbilang;
}
