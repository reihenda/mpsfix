<?php

namespace App\Http\Controllers;

use App\Models\DataPencatatan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DataPencatatanController extends Controller
{
    // Helper function to ensure data is always an array
    private function ensureArray($data)
    {
        if (is_string($data)) {
            return json_decode($data, true) ?? [];
        }

        if (is_array($data)) {
            return $data;
        }

        return [];
    }
    // Menampilkan data pencatatan untuk customer yang sedang login
    public function indexCustomer(Request $request)
    {
        // Dapatkan user yang sedang login (customer)
        $customer = Auth::user();

        // Get filter parameters
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        // Default to current month and year if not specified
        if (!$bulan) {
            $bulan = date('m');
        }
        if (!$tahun) {
            $tahun = date('Y');
        }

        // Format filter untuk query
        $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

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

        // Urutkan data berdasarkan tanggal pembacaan awal
        $dataPencatatan = $dataPencatatan->sortBy(function ($item) {
            $dataInput = $this->ensureArray($item->data_input);
            return isset($dataInput['pembacaan_awal']['waktu']) ?
                Carbon::parse($dataInput['pembacaan_awal']['waktu'])->timestamp : 0;
        });

        // Calculate total volume SM3 for all time
        $totalVolumeSm3 = $customer->getTotalVolumeSm3();

        // Calculate total volume SM3 for filtered period
        $filteredVolumeSm3 = 0;
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($customer->koreksi_meter);
            $filteredVolumeSm3 += $volumeSm3;
        }

        // Calculate total purchases for the filtered period
        $filteredTotalPurchases = $dataPencatatan->sum(function ($item) use ($customer) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($customer->koreksi_meter);
            return $volumeSm3 * floatval($customer->harga_per_meter_kubik);
        });

        // Hitung berapa data yang belum lunas
        $belumLunas = $customer->dataPencatatan()->where('status_pembayaran', 'belum_lunas')->count();

        return view('dashboard.customer', [
            'dataPencatatan' => $dataPencatatan,
            'selectedBulan' => $bulan,
            'selectedTahun' => $tahun,
            'totalVolumeSm3' => $totalVolumeSm3,
            'filteredVolumeSm3' => $filteredVolumeSm3,
            'filteredTotalPurchases' => $filteredTotalPurchases,
            'belumLunas' => $belumLunas,
            'totalTagihan' => $customer->dataPencatatan()->where('status_pembayaran', 'belum_lunas')->sum('harga_final')
        ]);
    }

    /**
     * Download template Excel untuk import data pencatatan
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplateExcel()
    {
        try {
            // Buat spreadsheet baru
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set judul sheet
            $sheet->setTitle('Template Pencatatan');

            // Header row 1
            $sheet->setCellValue('A1', 'No');
            $sheet->setCellValue('B1', 'Pembacaan Awal');
            $sheet->setCellValue('E1', 'Pembacaan Akhir');
            $sheet->mergeCells('B1:D1');
            $sheet->mergeCells('E1:G1');

            // Header row 2
            $sheet->setCellValue('A2', '');
            $sheet->setCellValue('B2', 'Tanggal');
            $sheet->setCellValue('C2', 'Jam');
            $sheet->setCellValue('D2', 'Meter');
            $sheet->setCellValue('E2', 'Tanggal');
            $sheet->setCellValue('F2', 'Jam');
            $sheet->setCellValue('G2', 'Meter');

            // Contoh data
            $sheet->setCellValue('A3', '1');
            $sheet->setCellValue('B3', '1-May-24');
            $sheet->setCellValue('C3', '7:00');
            $sheet->setCellValue('D3', '1928.20');
            $sheet->setCellValue('E3', '1-May-24');
            $sheet->setCellValue('F3', '18:00');
            $sheet->setCellValue('G3', '1928.20');

            $sheet->setCellValue('A4', '2');
            $sheet->setCellValue('B4', '2-May-24');
            $sheet->setCellValue('C4', '7:00');
            $sheet->setCellValue('D4', '1928.20');
            $sheet->setCellValue('E4', '2-May-24');
            $sheet->setCellValue('F4', '18:00');
            $sheet->setCellValue('G4', '2057.98');

            // Style untuk header
            $headerStyle = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'D9EAD3',
                    ],
                ],
            ];

            // Style untuk baris data
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];

            // Style untuk header row 1
            $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

            // Style untuk header row 2
            $sheet->getStyle('A2:G2')->applyFromArray($headerStyle);

            // Style untuk contoh data
            $sheet->getStyle('A3:G4')->applyFromArray($dataStyle);

            // Set lebar kolom
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(12);
            $sheet->getColumnDimension('C')->setWidth(8);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(12);
            $sheet->getColumnDimension('F')->setWidth(8);
            $sheet->getColumnDimension('G')->setWidth(12);

            // Set format angka untuk kolom meter
            $sheet->getStyle('D3:D1000')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('G3:G1000')->getNumberFormat()->setFormatCode('#,##0.00');

            // Tulis file ke tmp dan kirim ke browser
            $writer = new Xlsx($spreadsheet);
            $filename = 'template_data_pencatatan_' . date('Ymd') . '.xlsx';
            $temp_file = tempnam(sys_get_temp_dir(), $filename);
            $writer->save($temp_file);

            return response()->download($temp_file, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error creating Excel template: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Gagal membuat template Excel: ' . $e->getMessage());
        }
    }
    // Menampilkan daftar customer dan FOB
    public function index()
    {
        // Fetch only customers (not admin or superadmin)
        $customers = User::where('role', User::ROLE_CUSTOMER)->get();
        // Fetch FOB users
        $fobs = User::where('role', User::ROLE_FOB)->get();
        return view('data-pencatatan.index', compact('customers', 'fobs'));
    }

    // Menampilkan detail pencatatan untuk customer tertentu
    public function customerDetail(User $customer, Request $request)
    {
        // Get filter parameters
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        // Default to current month and year if not specified
        if (!$bulan) {
            $bulan = date('m');
        }
        if (!$tahun) {
            $tahun = date('Y');
        }

        // Cek apakah perlu refresh data
        $refresh = $request->has('refresh');

        // Jika ada request refresh, rekalkulasi total_purchases dan update saldo bulanan
        if ($refresh) {
            \Log::info('Melakukan rekalkulasi data karena parameter refresh', [
                'customer_id' => $customer->id,
                'bulan' => $bulan,
                'tahun' => $tahun
            ]);

            // Rekalkulasi total_purchases
            $newTotal = app(UserController::class)->rekalkulasiTotalPembelian($customer);

            \Log::info('Hasil rekalkulasi total_purchases', [
                'customer_id' => $customer->id,
                'total_purchases' => $newTotal
            ]);

            // Update saldo bulanan
            $updateResult = $customer->updateMonthlyBalances();

            \Log::info('Hasil update bulanan', [
                'customer_id' => $customer->id,
                'success' => $updateResult
            ]);

            // Reload customer untuk mendapatkan data terbaru
            $customer = User::findOrFail($customer->id);

            // Tambahkan pesan sukses jika refresh dilakukan
            if (!$request->has('skip_flash')) {
                \Session::flash('success', 'Data berhasil diselaraskan');
            }
        }

        // Selalu refresh data jika ada perubahan harga atau deposit
        $customer->updateMonthlyBalances();

        // Format filter untuk query
        $yearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

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
        // Urutkan data berdasarkan tanggal pembacaan awal (ascending - dari awal ke akhir)
        $dataPencatatan = $dataPencatatan->sortBy(function ($item) {
            $dataInput = $this->ensureArray($item->data_input);
            return isset($dataInput['pembacaan_awal']['waktu']) ?
                Carbon::parse($dataInput['pembacaan_awal']['waktu'])->timestamp : 0;
        });


        // Get pricing info for selected month
        $pricingInfo = $customer->getPricingForYearMonth($yearMonth);

        // Calculate total volume SM3 for all time
        $totalVolumeSm3 = $customer->getTotalVolumeSm3();

        // Calculate total volume SM3 for filtered period
        $filteredVolumeSm3 = 0;
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);

            // Ambil waktu untuk mendapatkan pricing yang tepat
            $waktuAwalYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $pricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
            $filteredVolumeSm3 += $volumeSm3;
        }

        // Calculate total purchases for the filtered period
        $filteredTotalPurchases = 0;
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);

            // Ambil waktu untuk mendapatkan pricing yang tepat
            $waktuAwalYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $pricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
            $volumeSm3 = $volumeFlowMeter * floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
            $filteredTotalPurchases += $volumeSm3 * floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
        }

        // Calculate total deposits for the filtered period (current month only)
        $filteredTotalDeposits = 0;
        $depositHistory = $this->ensureArray($customer->deposit_history);

        // Format bulan saat ini untuk perbandingan konsisten
        $currentYearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                // Pastikan hanya deposit pada bulan dan tahun yang dipilih menggunakan format yang konsisten
                if ($depositDate->format('Y-m') === $currentYearMonth) {
                    $filteredTotalDeposits += floatval($deposit['amount'] ?? 0);
                }
            }
        }

        // Menggunakan saldo bulanan dari database
        // Mendapatkan bulan sebelumnya
        $prevDate = Carbon::createFromDate($tahun, $bulan, 1)->subMonth();
        $prevYearMonth = $prevDate->format('Y-m');
        $currentYearMonth = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT);

        // Pastikan saldo bulanan sudah terupdate
        if (!isset($customer->monthly_balances[$currentYearMonth])) {
            $customer->updateMonthlyBalances($prevYearMonth);
            // Reload customer untuk mendapatkan data terbaru
            $customer = User::findOrFail($customer->id);
        }

        // Ambil saldo bulanan
        $monthlyBalances = $customer->monthly_balances ?: [];

        // Ambil saldo bulan sebelumnya dan bulan ini
        $prevMonthBalance = isset($monthlyBalances[$prevYearMonth]) ?
            floatval($monthlyBalances[$prevYearMonth]) : 0;

        $currentMonthBalance = isset($monthlyBalances[$currentYearMonth]) ?
            floatval($monthlyBalances[$currentYearMonth]) : ($prevMonthBalance + $filteredTotalDeposits - $filteredTotalPurchases);

        $yearlyData = $this->calculateYearlyData($customer, $tahun);

        return view('data-pencatatan.customer-detail', [
            'customer' => $customer,
            'dataPencatatan' => $dataPencatatan,
            'depositHistory' => $customer->deposit_history ?? [],
            'totalDeposit' => $customer->total_deposit,
            'totalPurchases' => $customer->total_purchases,
            'currentBalance' => $customer->getCurrentBalance(),
            'selectedBulan' => $bulan,
            'selectedTahun' => $tahun,
            'pricingInfo' => $pricingInfo,
            'totalVolumeSm3' => $totalVolumeSm3,
            'filteredVolumeSm3' => $filteredVolumeSm3,
            'filteredTotalPurchases' => $filteredTotalPurchases,
            'filteredTotalDeposits' => $filteredTotalDeposits,
            'totalPemakaianTahunan' => $yearlyData['totalPemakaianTahunan'],
            'totalPembelianTahunan' => $yearlyData['totalPembelianTahunan'],
            'prevMonthBalance' => $prevMonthBalance,
            'currentMonthBalance' => $currentMonthBalance
        ]);
    }
    // Fungsi untuk menghitung informasi tahunan
    // Fungsi untuk menghitung informasi tahunan
    private function calculateYearlyData(User $customer, $tahun)
    {
        // Ambil semua data pencatatan
        $allData = $customer->dataPencatatan()->get();

        // Filter hanya data dari tahun yang dipilih
        $yearlyData = $allData->filter(function ($item) use ($tahun) {
            $dataInput = $this->ensureArray($item->data_input);

            // Jika data input kosong atau tidak ada waktu awal, skip
            if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                return false;
            }

            // Ambil tahun dari pembacaan awal
            $waktuAwalTahun = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y');

            // Filter by year
            return $waktuAwalTahun === $tahun;
        });

        // Hitung total pemakaian
        $totalPemakaianTahunan = 0;
        foreach ($yearlyData as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

            // Ambil waktu untuk mendapatkan pricing yang tepat
            $waktuAwalYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $pricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

            // Gunakan koreksi meter yang sesuai
            $koreksiMeter = floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
            $volumeSm3 = $volumeFlowMeter * $koreksiMeter;

            $totalPemakaianTahunan += $volumeSm3;
        }

        // Hitung total pembelian berdasarkan volume Sm3 dan harga per meter kubik
        $totalPembelianTahunan = 0;
        foreach ($yearlyData as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

            // Ambil waktu untuk mendapatkan pricing yang tepat
            $waktuAwalYearMonth = Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('Y-m');
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $pricingInfo = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

            // Gunakan koreksi meter dan harga yang sesuai untuk periode ini
            $koreksiMeter = floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
            $hargaPerMeterKubik = floatval($pricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);

            $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
            $pembelian = $volumeSm3 * $hargaPerMeterKubik;

            $totalPembelianTahunan += $pembelian;
        }

        // Debug untuk melihat proses perhitungan
        \Log::info("Total data pencatatan tahun $tahun: " . $yearlyData->count());
        \Log::info("Total pemakaian tahunan: $totalPemakaianTahunan Sm³");
        \Log::info("Total pembelian tahunan: Rp " . number_format($totalPembelianTahunan, 0));

        return [
            'totalPemakaianTahunan' => round($totalPemakaianTahunan, 2),
            'totalPembelianTahunan' => round($totalPembelianTahunan, 0) // Bulatkan ke angka bulat untuk Rupiah
        ];
    }

    // Fungsi untuk menghitung data bulanan
    private function calculateMonthlyData(User $customer, $yearMonth)
    {
        // Ambil semua data dulu
        $allData = $customer->dataPencatatan()->get();

        // Filter data berdasarkan bulan dan tahun dari pembacaan awal
        $filteredData = $allData->filter(function ($item) use ($yearMonth) {
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

        // Calculate total volume SM3 for filtered period
        $filteredVolumeSm3 = 0;
        foreach ($filteredData as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

            // Get pricing info for this period
            $pricingInfo = $customer->getPricingForYearMonth($yearMonth);
            $koreksiMeter = floatval($pricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);

            $volumeSm3 = $volumeFlowMeter * $koreksiMeter;
            $filteredVolumeSm3 += $volumeSm3;
        }

        // Calculate total purchases for the filtered period
        $filteredTotalPurchases = $filteredData->sum('harga_final');

        return [
            'filteredVolumeSm3' => $filteredVolumeSm3,
            'filteredTotalPurchases' => $filteredTotalPurchases
        ];
    }

    public function filterByMonthYear(Request $request, User $customer)
    {
        $validatedData = $request->validate([
            'bulan' => 'required|numeric|between:1,12',
            'tahun' => 'required|numeric|between:2000,2100'
        ]);

        return redirect()->route('data-pencatatan.customer-detail', [
            'customer' => $customer->id,
            'bulan' => $validatedData['bulan'],
            'tahun' => $validatedData['tahun']
        ]);
    }

    // Filter data by date range
    public function filterByDateRange(Request $request, User $customer)
    {
        $validatedData = $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_mulai'
        ]);

        return redirect()->route('data-pencatatan.customer-detail', [
            'customer' => $customer->id,
            'tanggal_mulai' => $validatedData['tanggal_mulai'] ?? null,
            'tanggal_akhir' => $validatedData['tanggal_akhir'] ?? null
        ]);
    }

    // Menampilkan form input data (hanya admin/superadmin)
    public function create(Request $request)
    {
        // Ambil daftar customer untuk dipilih
        $customers = User::where('role', User::ROLE_CUSTOMER)->get();

        // Check if a customer_id was passed from the customer detail page
        $selectedCustomerId = $request->query('customer_id');
        $selectedCustomer = null;

        if ($selectedCustomerId) {
            $selectedCustomer = User::find($selectedCustomerId);
        }

        return view('data-pencatatan.create', compact('customers', 'selectedCustomer'));
    }

    // Proses penyimpanan data
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:users,id',
            'data_input' => 'required|array'
        ]);

        // Flatten and sanitize data input
        $sanitizedDataInput = $this->sanitizeDataInput($validatedData['data_input']);

        // Validate specific input requirements
        $this->validateDataInput($sanitizedDataInput);

        $customer = User::findOrFail($validatedData['customer_id']);

        // Process skipped dates
        $this->processSkippedDates($customer, $sanitizedDataInput);

        // Konversi data input ke JSON
        $dataInput = json_encode($sanitizedDataInput);

        // Buat data pencatatan baru
        $dataPencatatan = new DataPencatatan();
        $dataPencatatan->customer_id = $validatedData['customer_id'];
        $dataPencatatan->data_input = $dataInput;
        $dataPencatatan->nama_customer = $customer->name;
        $dataPencatatan->status_pembayaran = 'belum_lunas'; // Default status

        // Hitung harga otomatis
        $dataPencatatan->hitungHarga();

        $dataPencatatan->save();

        // Rekalkulasi total pembelian customer setelah menambah data baru
        app(UserController::class)->rekalkulasiTotalPembelian($customer);

        // Ambil waktu pembacaan awal untuk menentukan bulan mulai update saldo
        $waktuPencatatan = Carbon::parse($sanitizedDataInput['pembacaan_awal']['waktu']);
        $startMonth = $waktuPencatatan->format('Y-m');

        // Update saldo bulanan mulai dari bulan data baru
        $customer->updateMonthlyBalances($startMonth);

        // Logging untuk debug
        \Log::info('Data pencatatan berhasil disimpan', [
            'record_id' => $dataPencatatan->id,
            'customer_id' => $customer->id,
            'date' => $waktuPencatatan->format('Y-m-d H:i:s'),
            'harga_final' => $dataPencatatan->harga_final,
            'customer_total_purchases' => $customer->total_purchases,
            'customer_total_deposit' => $customer->total_deposit,
            'customer_saldo' => $customer->total_deposit - $customer->total_purchases
        ]);

        return redirect()->route('data-pencatatan.customer-detail', [
            'customer' => $validatedData['customer_id'],
            'refresh' => true
        ])->with('success', 'Data berhasil disimpan');
    }

    // Process skipped dates and create entries for them
    private function processSkippedDates(User $customer, array $currentData)
    {
        // Mendapatkan data pencatatan terakhir sebelum data saat ini
        $latestEntry = $customer->dataPencatatan()
            ->get()
            ->filter(function ($item) use ($currentData) {
                $dataInput = $this->ensureArray($item->data_input);

                // Skip jika data tidak lengkap
                if (empty($dataInput) || empty($dataInput['pembacaan_akhir']['waktu'])) {
                    return false;
                }

                // Cek apakah tanggal akhir dari data sebelumnya berada sebelum tanggal awal data saat ini
                $waktuAkhir = Carbon::parse($dataInput['pembacaan_akhir']['waktu']);
                $currentWaktuAwal = Carbon::parse($currentData['pembacaan_awal']['waktu']);

                return $waktuAkhir->lt($currentWaktuAwal);
            })
            ->sortByDesc(function ($item) {
                $dataInput = $this->ensureArray($item->data_input);
                return isset($dataInput['pembacaan_akhir']['waktu']) ?
                    Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->timestamp : 0;
            })
            ->first();

        // Jika tidak ada data sebelumnya, tidak ada yang perlu diproses
        if (!$latestEntry) {
            return;
        }

        $latestData = $this->ensureArray($latestEntry->data_input);

        // Cek apakah ada gap antara data terakhir dan data saat ini
        $latestEndDate = Carbon::parse($latestData['pembacaan_akhir']['waktu']);
        $currentStartDate = Carbon::parse($currentData['pembacaan_awal']['waktu']);

        // Jika gap kurang dari 1 hari, tidak perlu diproses
        if ($latestEndDate->diffInDays($currentStartDate) < 1) {
            return;
        }

        // MODIFIKASI: Hanya buat data untuk tanggal yang sepenuhnya terlewat
        // Ambil tanggal dari waktu pembacaan awal saat ini (tanpa jam)
        $currentStartDay = $currentStartDate->copy()->startOfDay();

        // Membuat array tanggal untuk setiap hari yang terlewat
        $dates = [];
        $date = $latestEndDate->copy()->addDay()->startOfDay();

        // Hanya proses tanggal hingga sehari sebelum tanggal awal data baru
        while ($date->lt($currentStartDay)) {
            $dates[] = $date->copy();
            $date->addDay();
        }

        // Membuat entri data untuk setiap tanggal yang terlewat
        foreach ($dates as $date) {
            // Buat entri dengan nilai pembacaan awal dan akhir yang sama
            $skippedData = [
                'pembacaan_awal' => [
                    'waktu' => $date->format('Y-m-d 00:00'),
                    'volume' => $latestData['pembacaan_akhir']['volume']
                ],
                'pembacaan_akhir' => [
                    'waktu' => $date->copy()->format('Y-m-d 23:59'),
                    'volume' => $latestData['pembacaan_akhir']['volume']
                ],
                'volume_flow_meter' => 0
            ];

            // Konversi ke JSON
            $dataInput = json_encode($skippedData);

            // Buat entri baru
            $dataPencatatan = new DataPencatatan();
            $dataPencatatan->customer_id = $customer->id;
            $dataPencatatan->data_input = $dataInput;
            $dataPencatatan->nama_customer = $customer->name;
            $dataPencatatan->status_pembayaran = 'lunas'; // Otomatis dianggap lunas karena tidak ada perubahan volume
            $dataPencatatan->harga_final = 0; // Harga nol karena tidak ada konsumsi

            $dataPencatatan->save();
        }
    }

    // Menampilkan detail data
    public function show(DataPencatatan $dataPencatatan)
    {
        // Cek otorisasi
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isSuperAdmin() && $dataPencatatan->customer_id !== $user->id) {
            abort(403, 'Unauthorized access');
        }

        return view('data-pencatatan.show', compact('dataPencatatan'));
    }

    // Edit data
    public function edit(DataPencatatan $dataPencatatan)
    {
        $customers = User::where('role', User::ROLE_CUSTOMER)->get();
        return view('data-pencatatan.edit', compact('dataPencatatan', 'customers'));
    }

    // Update data
    public function update(Request $request, DataPencatatan $dataPencatatan)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:users,id',
            'data_input' => 'required|array'
        ]);

        // Flatten and sanitize data input
        $sanitizedDataInput = $this->sanitizeDataInput($validatedData['data_input']);

        // Validate specific input requirements
        $this->validateDataInput($sanitizedDataInput);

        // Simpan data lama untuk perbandingan
        $oldData = $this->ensureArray($dataPencatatan->data_input);
        $oldMonth = !empty($oldData['pembacaan_awal']['waktu'])
            ? Carbon::parse($oldData['pembacaan_awal']['waktu'])->format('Y-m')
            : null;

        // Logging data lama untuk debugging
        \Log::debug('Data lama sebelum update', [
            'record_id' => $dataPencatatan->id,
            'old_data' => $oldData,
            'old_volume_flow_meter' => floatval($oldData['volume_flow_meter'] ?? 0),
            'old_harga_final' => $dataPencatatan->harga_final,
            'old_month' => $oldMonth
        ]);

        // Konversi data input ke JSON
        $dataInput = json_encode($sanitizedDataInput);

        $customer = User::findOrFail($validatedData['customer_id']);
        $dataPencatatan->customer_id = $validatedData['customer_id'];
        $dataPencatatan->data_input = $dataInput;
        $dataPencatatan->nama_customer = $customer->name;

        // Simpan harga lama sebelum dihitung ulang
        $oldHarga = $dataPencatatan->harga_final;

        // Hitung ulang harga
        $dataPencatatan->hitungHarga();

        $dataPencatatan->save();

        // Logging data baru untuk debugging
        \Log::debug('Data baru setelah update', [
            'record_id' => $dataPencatatan->id,
            'new_volume_flow_meter' => floatval($sanitizedDataInput['volume_flow_meter'] ?? 0),
            'new_harga_final' => $dataPencatatan->harga_final,
            'harga_difference' => $dataPencatatan->harga_final - $oldHarga
        ]);

        // Rekalkulasi total pembelian customer setelah update data
        app(UserController::class)->rekalkulasiTotalPembelian($customer);

        // Ambil waktu pembacaan awal baru untuk menentukan bulan mulai update saldo
        $waktuPencatatan = Carbon::parse($sanitizedDataInput['pembacaan_awal']['waktu']);
        $newMonth = $waktuPencatatan->format('Y-m');

        // Tentukan bulan mulai untuk update saldo (pilih yang lebih awal)
        $startMonth = $oldMonth && $oldMonth < $newMonth ? $oldMonth : $newMonth;

        // Update saldo bulanan mulai dari bulan data
        $customer->updateMonthlyBalances($startMonth);

        // Logging untuk debug
        \Log::info('Data pencatatan berhasil diupdate', [
            'record_id' => $dataPencatatan->id,
            'customer_id' => $customer->id,
            'new_date' => $waktuPencatatan->format('Y-m-d H:i:s'),
            'customer_total_purchases' => $customer->total_purchases,
            'customer_total_deposit' => $customer->total_deposit,
            'customer_saldo' => $customer->total_deposit - $customer->total_purchases
        ]);

        return redirect()->route('data-pencatatan.customer-detail', [
            'customer' => $validatedData['customer_id'],
            'refresh' => true
        ])->with('success', 'Data berhasil diupdate');
    }

    public function updateCustomerPricing(Request $request, User $customer)
    {
        $validatedData = $request->validate([
            'harga_per_meter_kubik' => 'required|numeric|min:0',
            'tekanan_keluar' => 'required|numeric',
            'suhu' => 'required|numeric',
            'koreksi_meter' => 'required|numeric'
        ]);

        // Perform koreksi meter calculation to verify
        $A = (floatval($validatedData['tekanan_keluar']) + 1.01325) / 1.01325;
        $B = 300 / (floatval($validatedData['suhu']) + 273);
        $C = 1 + 0.002 * floatval($validatedData['tekanan_keluar']);
        $calculatedKoreksiMeter = $A * $B * $C;

        // Check if calculated result matches the provided result (with small tolerance)
        if (abs($calculatedKoreksiMeter - floatval($validatedData['koreksi_meter'])) > 0.0001) {
            return back()->with('error', 'Perhitungan koreksi meter tidak sesuai');
        }

        // Update customer pricing information
        $customer->harga_per_meter_kubik = floatval($validatedData['harga_per_meter_kubik']);
        $customer->tekanan_keluar = floatval($validatedData['tekanan_keluar']);
        $customer->suhu = floatval($validatedData['suhu']);
        $customer->koreksi_meter = floatval($validatedData['koreksi_meter']);
        $customer->save();

        return back()->with('success', 'Harga dan koreksi meter berhasil diperbarui');
    }

    // Optional: Method to get customer details
    public function getCustomerDetails(User $customer)
    {
        return response()->json([
            'name' => $customer->name,
            'email' => $customer->email,
            'harga_per_meter_kubik' => $customer->harga_per_meter_kubik ?? 0,
            'tekanan_keluar' => $customer->tekanan_keluar ?? 0,
            'suhu' => $customer->suhu ?? 0,
            'koreksi_meter' => $customer->koreksi_meter ?? 1
        ]);
    }
    // Get the latest reading data for a customer
    public function getLatestReading(Request $request)
    {
        $customerId = $request->input('customer_id');

        if (!$customerId) {
            return response()->json(['error' => 'Missing customer ID', 'success' => false], 400);
        }

        // Find the customer
        $customer = User::find($customerId);
        if (!$customer) {
            return response()->json(['error' => 'Customer not found', 'success' => false], 404);
        }

        try {
            // Get the most recent entry for this customer
            $latestEntry = $customer->dataPencatatan()
                ->get()
                ->filter(function ($item) {
                    $dataInput = $this->ensureArray($item->data_input);
                    return !empty($dataInput) && !empty($dataInput['pembacaan_akhir']['waktu']) && isset($dataInput['pembacaan_akhir']['volume']);
                })
                ->sortByDesc(function ($item) {
                    $dataInput = $this->ensureArray($item->data_input);
                    return Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->timestamp;
                })
                ->first();

            if ($latestEntry) {
                $dataInput = $this->ensureArray($latestEntry->data_input);
                $latestDate = Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->format('d-m-Y H:i');

                return response()->json([
                    'success' => true,
                    'data' => [
                        'volume' => floatval($dataInput['pembacaan_akhir']['volume'] ?? 0),
                        'date' => $latestDate,
                        'message' => 'Menggunakan data pembacaan terakhir'
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data pembacaan sebelumnya'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage(),
                'message' => 'Terjadi kesalahan saat memproses data'
            ]);
        }
    }
    // Di DataPencatatanController.php, tambahkan method baru:
    public function createWithCustomer(Request $request, $customerId)
    {
        // Ambil daftar customer untuk dipilih
        $customers = User::where('role', User::ROLE_CUSTOMER)->get();

        // Get the selected customer
        $selectedCustomer = User::findOrFail($customerId);

        // Get the latest reading data for this customer
        $latestData = null;
        $latestVolume = null;
        $latestDate = null;

        // Find the latest entry
        $latestEntry = $selectedCustomer->dataPencatatan()
            ->get()
            ->filter(function ($item) {
                $dataInput = $this->ensureArray($item->data_input);
                return !empty($dataInput) && !empty($dataInput['pembacaan_akhir']['waktu']) && isset($dataInput['pembacaan_akhir']['volume']);
            })
            ->sortByDesc(function ($item) {
                $dataInput = $this->ensureArray($item->data_input);
                return Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->timestamp;
            })
            ->first();

        if ($latestEntry) {
            $dataInput = $this->ensureArray($latestEntry->data_input);
            $latestVolume = floatval($dataInput['pembacaan_akhir']['volume'] ?? 0);
            $latestDate = Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->format('d-m-Y H:i');
            session()->flash('success', 'Data pembacaan terakhir berhasil diambil');
        }

        return view('data-pencatatan.create', compact('customers', 'selectedCustomer', 'latestVolume', 'latestDate'));
    }

    // Sanitize and flatten nested input data
    private function sanitizeDataInput(array $dataInput)
    {
        $sanitized = [];

        // Sanitize pembacaan awal
        if (isset($dataInput['pembacaan_awal'])) {
            $sanitized['pembacaan_awal'] = [
                'waktu' => is_array($dataInput['pembacaan_awal']['waktu'] ?? null)
                    ? ''
                    : ($dataInput['pembacaan_awal']['waktu'] ?? ''),
                'volume' => is_array($dataInput['pembacaan_awal']['volume'] ?? null)
                    ? 0
                    : floatval($dataInput['pembacaan_awal']['volume'] ?? 0)
            ];
        }

        // Sanitize pembacaan akhir
        if (isset($dataInput['pembacaan_akhir'])) {
            $sanitized['pembacaan_akhir'] = [
                'waktu' => is_array($dataInput['pembacaan_akhir']['waktu'] ?? null)
                    ? ''
                    : ($dataInput['pembacaan_akhir']['waktu'] ?? ''),
                'volume' => is_array($dataInput['pembacaan_akhir']['volume'] ?? null)
                    ? 0
                    : floatval($dataInput['pembacaan_akhir']['volume'] ?? 0)
            ];
        }

        // Sanitize volume flow meter
        $sanitized['volume_flow_meter'] = isset($dataInput['volume_flow_meter'])
            ? (is_array($dataInput['volume_flow_meter'])
                ? 0
                : floatval($dataInput['volume_flow_meter']))
            : 0;

        return $sanitized;
    }

    // Validasi khusus untuk input data
    private function validateDataInput(array $dataInput)
    {
        // Validasi pembacaan awal
        if (!isset($dataInput['pembacaan_awal']['waktu']) || !isset($dataInput['pembacaan_awal']['volume'])) {
            throw new \InvalidArgumentException('Data pembacaan awal tidak lengkap');
        }

        // Validasi pembacaan akhir
        if (!isset($dataInput['pembacaan_akhir']['waktu']) || !isset($dataInput['pembacaan_akhir']['volume'])) {
            throw new \InvalidArgumentException('Data pembacaan akhir tidak lengkap');
        }

        // Validasi volume
        $volumeAwal = floatval($dataInput['pembacaan_awal']['volume']);
        $volumeAkhir = floatval($dataInput['pembacaan_akhir']['volume']);

        if ($volumeAkhir < $volumeAwal) {
            throw new \InvalidArgumentException('Volume akhir tidak boleh kurang dari volume awal');
        }

        // Validasi waktu
        $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
        $waktuAkhir = Carbon::parse($dataInput['pembacaan_akhir']['waktu']);

        if ($waktuAkhir <= $waktuAwal) {
            throw new \InvalidArgumentException('Waktu pembacaan akhir harus lebih besar dari waktu pembacaan awal');
        }

        // Validasi volume flow meter
        $volumeFlowMeter = $volumeAkhir - $volumeAwal;

        // Pastikan perhitungan volume flow meter sesuai
        if (abs($volumeFlowMeter - floatval($dataInput['volume_flow_meter'])) > 0.001) {
            throw new \InvalidArgumentException('Perhitungan volume flow meter tidak sesuai');
        }
    }

    // Proses pembayaran (optional)
    public function prosesPembayaran(DataPencatatan $dataPencatatan)
    {
        // Validasi apakah user yang sedang login adalah customer dari data ini
        $user = Auth::user();
        if (!$user->isCustomer() || $dataPencatatan->customer_id !== $user->id) {
            abort(403, 'Unauthorized access');
        }

        $dataPencatatan->status_pembayaran = 'lunas';
        $dataPencatatan->save();

        return redirect()->back()->with('success', 'Pembayaran berhasil diproses');
    }

    // Hapus data
    public function destroy(Request $request, DataPencatatan $dataPencatatan)
    {
        // Ambil informasi tanggal sebelum data dihapus
        $dataInput = $this->ensureArray($dataPencatatan->data_input);
        $customer_id = $dataPencatatan->customer_id;
        $customer = User::findOrFail($customer_id);

        // Logging data yang akan dihapus
        \Log::info('Menghapus data pencatatan', [
            'record_id' => $dataPencatatan->id,
            'customer_id' => $customer_id,
            'harga_final' => $dataPencatatan->harga_final,
            'data_input' => $dataInput
        ]);

        // Ambil waktu pembacaan awal untuk menentukan bulan mulai update saldo
        $startMonth = null;
        $tanggalSearch = null;
        if (!empty($dataInput['pembacaan_awal']['waktu'])) {
            $waktuPencatatan = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $startMonth = $waktuPencatatan->format('Y-m');
        }

        // Jika waktu di pembacaan_awal tidak ditemukan, cek apakah ini data FOB
        if (!empty($dataInput['waktu'])) {
            $waktuPencatatan = Carbon::parse($dataInput['waktu']);
            $startMonth = $waktuPencatatan->format('Y-m');
            $tanggalSearch = $waktuPencatatan->format('Y-m-d');

            // Jika customer adalah FOB, hapus juga data rekap pengambilan yang terkait
            if ($customer->isFOB()) {
                \App\Models\RekapPengambilan::where('customer_id', $customer_id)
                    ->whereDate('tanggal', $tanggalSearch)
                    ->delete();

                \Illuminate\Support\Facades\Log::info('Menghapus data rekap pengambilan FOB', [
                    'customer_id' => $customer_id,
                    'tanggal' => $tanggalSearch
                ]);
            }
        }

        // Ambil nilai total sebelum penghapusan untuk perbandingan
        $oldTotalPurchases = $customer->total_purchases;

        // Hapus data pencatatan
        $dataPencatatan->delete();

        // Rekalkulasi total pembelian customer setelah menghapus data
        $newTotalPurchases = 0;
        if ($customer->isFOB()) {
            $newTotalPurchases = app(UserController::class)->rekalkulasiTotalPembelianFob($customer);
        } else {
            $newTotalPurchases = app(UserController::class)->rekalkulasiTotalPembelian($customer);
        }

        // Logging perubahan total
        \Log::info('Perubahan total setelah hapus data', [
            'customer_id' => $customer_id,
            'old_total_purchases' => $oldTotalPurchases,
            'new_total_purchases' => $newTotalPurchases,
            'difference' => $oldTotalPurchases - $newTotalPurchases,
            'deleted_harga_final' => $dataPencatatan->harga_final
        ]);

        // Update saldo bulanan mulai dari bulan data yang dihapus
        if ($startMonth) {
            $customer->updateMonthlyBalances($startMonth);
        }

        // Ambil bulan dan tahun dari request jika ada
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));
        $isFob = $request->input('fob', 0);

        if ($isFob) {
            return redirect()->route('data-pencatatan.fob-detail', [
                'customer' => $customer_id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'refresh' => true
            ])->with('success', 'Data berhasil dihapus');
        } else {
            return redirect()->route('data-pencatatan.customer-detail', [
                'customer' => $customer_id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'refresh' => true
            ])->with('success', 'Data berhasil dihapus');
        }
    }
    // Method untuk mencetak billing dalam bentuk HTML (dapat diprint oleh browser)
    public function printBilling(Request $request, User $customer)
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

        $i = 1;
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

            // Ambil pricing info berdasarkan tanggal spesifik
            $waktuAwalYearMonth = $waktuAwal->format('Y-m');
            $pricingInfoItem = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

            $volumeSm3 = $volumeFlowMeter * floatval($pricingInfoItem['koreksi_meter'] ?? $customer->koreksi_meter);
            $hargaGas = floatval($pricingInfoItem['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $biayaPemakaian = $volumeSm3 * $hargaGas;

            $periodeMulai = isset($dataInput['pembacaan_awal']['waktu']) ?
                Carbon::parse($dataInput['pembacaan_awal']['waktu'])->format('d/m/Y') : '';
            $periodeSelesai = isset($dataInput['pembacaan_akhir']['waktu']) ?
                Carbon::parse($dataInput['pembacaan_akhir']['waktu'])->format('d/m/Y') : '';
            $periodePemakaian = $periodeMulai . ' - ' . $periodeSelesai;

            $pemakaianGas[] = [
                'no' => $i++,
                'periode_pemakaian' => $periodePemakaian,
                'volume_sm3' => $volumeSm3,
                'harga_gas' => $hargaGas,
                'biaya_pemakaian' => $biayaPemakaian
            ];

            $totalVolume += $volumeSm3;
            $totalBiaya += $biayaPemakaian;
        }

        // Perhitungan untuk penerimaan deposit
        $penerimaanDeposit = [];
        $totalDeposit = 0;

        $depositHistory = $this->ensureArray($customer->deposit_history);
        $j = 1;
        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate->month == $bulan && $depositDate->year == $tahun) {
                    $jumlahDeposit = floatval($deposit['amount'] ?? 0);
                    $penerimaanDeposit[] = [
                        'no' => $j++,
                        'tanggal_deposit' => $depositDate->format('d/m/Y'),
                        'jumlah_penerimaan' => $jumlahDeposit
                    ];
                    $totalDeposit += $jumlahDeposit;
                }
            }
        }

        // Menghitung saldo bulan sebelumnya
        $prevDate = Carbon::createFromDate($tahun, $bulan, 1)->subMonth();
        $prevMonthYear = $prevDate->format('Y-m');

        // Mendapatkan deposit dan pembelian pada semua periode sebelumnya
        $prevTotalDeposits = 0;
        $prevTotalPurchases = 0;

        // Menghitung deposit seluruh periode sebelumnya
        foreach ($depositHistory as $deposit) {
            if (isset($deposit['date'])) {
                $depositDate = Carbon::parse($deposit['date']);
                if ($depositDate < Carbon::createFromDate($tahun, $bulan, 1)) {
                    $prevTotalDeposits += floatval($deposit['amount'] ?? 0);
                }
            }
        }

        // Menghitung pembelian seluruh periode sebelumnya
        $allData = $customer->dataPencatatan()->get();
        foreach ($allData as $item) {
            $dataInput = $this->ensureArray($item->data_input);

            // Jika data input kosong atau tidak ada waktu awal, skip
            if (empty($dataInput) || empty($dataInput['pembacaan_awal']['waktu'])) {
                continue;
            }

            $itemDate = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            if ($itemDate < Carbon::createFromDate($tahun, $bulan, 1)) {
                $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);
                $itemYearMonth = $itemDate->format('Y-m');
                $itemPricingInfo = $customer->getPricingForYearMonth($itemYearMonth);
                $volumeSm3 = $volumeFlowMeter * floatval($itemPricingInfo['koreksi_meter'] ?? $customer->koreksi_meter);
                $prevTotalPurchases += $volumeSm3 * floatval($itemPricingInfo['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            }
        }

        // Menghitung saldo bulan sebelumnya
        $prevMonthBalance = $prevTotalDeposits - $prevTotalPurchases;

        // Menghitung saldo bulan ini
        $currentMonthBalance = $prevMonthBalance + $totalDeposit - $totalBiaya;

        // Menghitung biaya yang harus dibayar (jika saldo negatif)
        $biayaYangHarusDibayar = $currentMonthBalance < 0 ? abs($currentMonthBalance) : 0;

        // Setup data untuk HTML Billing
        $data = [
            'customer' => $customer,
            'periode_bulan' => Carbon::createFromDate($tahun, $bulan, 1)->format('F Y'),
            'pemakaian_gas' => $pemakaianGas,
            'total_volume' => $totalVolume,
            'total_biaya' => $totalBiaya,
            'penerimaan_deposit' => $penerimaanDeposit,
            'total_deposit' => $totalDeposit,
            'saldo_bulan_lalu' => $prevMonthBalance,
            'sisa_saldo' => $currentMonthBalance,
            'biaya_yang_harus_dibayar' => $biayaYangHarusDibayar
        ];

        // Return view HTML yang dapat dicetak
        return view('pdf.billing', $data);
    }    // Method untuk mencetak billing dalam bentuk PDF
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

        $i = 1;
        foreach ($dataPencatatan as $item) {
            $dataInput = $this->ensureArray($item->data_input);
            $waktuAwal = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
            $volumeFlowMeter = floatval($dataInput['volume_flow_meter'] ?? 0);

            // Ambil pricing info berdasarkan tanggal spesifik
            $waktuAwalYearMonth = $waktuAwal->format('Y-m');
            $pricingInfoItem = $customer->getPricingForYearMonth($waktuAwalYearMonth, $waktuAwal);

            $volumeSm3 = $volumeFlowMeter * floatval($pricingInfoItem['koreksi_meter'] ?? $customer->koreksi_meter);
            $hargaGas = floatval($pricingInfoItem['harga_per_meter_kubik'] ?? $customer->harga_per_meter_kubik);
            $biayaPemakaian = $volumeSm3 * $hargaGas;

            // Format periode pemakaian
            $periode = Carbon::parse($dataInput['pembacaan_awal']['waktu'] ?? now())->format('d/m/Y');
            $periodePemakaian = Carbon::parse($dataInput['pembacaan_awal']['waktu'] ?? now())->format('1 F') . " - " .
                Carbon::parse($dataInput['pembacaan_awal']['waktu'] ?? now())->endOfMonth()->format('d F Y');

            $pemakaianGas[] = [
                'no' => $i++,
                'periode_pemakaian' => $periodePemakaian,
                'volume_sm3' => $volumeSm3,
                'harga_gas' => $hargaGas,
                'biaya_pemakaian' => $biayaPemakaian
            ];

            $totalVolume += $volumeSm3;
            $totalBiaya += $biayaPemakaian;
        }

        // Generate nomor invoice
        $nomorInvoice = sprintf('%03d/MPS/INV-NOMI/II/%s', $i, date('Y'));

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
    private function terbilang($angka)
    {
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
}
