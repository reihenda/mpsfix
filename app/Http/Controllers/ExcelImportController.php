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
use App\Http\Controllers\ExcelHelpers;
use App\Http\Controllers\UserController;

class ExcelImportController extends Controller
{
    /**
     * Import data dari file Excel ke data pencatatan customer
     *
     * @param Request $request
     * @param User $customer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importExcel(Request $request, User $customer)
    {
        // Validasi input
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // max 10MB
        ]);

        // Ambil file Excel
        $file = $request->file('excel_file');
        $skipValidation = $request->has('skip_validation');

        try {
            // Load Excel file
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Menentukan header dan mulai dari baris data (biasanya baris ke-3)
            $dataStartRow = 2; // Mulai dari baris ke-3 (index 2)

            // Total rows yang berhasil diimport dan error
            $importedCount = 0;
            $errorCount = 0;
            $errors = [];
            $allDataValid = true; // Flag untuk menandai apakah semua data valid

            // Mulai transaksi database
            DB::beginTransaction();

            // Loop through rows mulai dari baris data
            for ($i = $dataStartRow; $i < count($rows); $i++) {
                // Skip baris kosong
                if (empty($rows[$i][0]) && empty($rows[$i][1]) && empty($rows[$i][2])) {
                    continue;
                }

                // Parse data Excel
                try {
                    // Parse input data
                    $rowNumber = $i + 1; // Untuk pesan error (baris di Excel)
                    $dataRow = $rows[$i];

                    // Cek apakah row memiliki cukup kolom
                    if (count($dataRow) < 7) {
                        $errors[] = "Baris $rowNumber: Format data tidak valid, kurang dari 7 kolom";
                        $errorCount++;
                        $allDataValid = false; // Tandai bahwa tidak semua data valid
                        continue;
                    }

                    // Parse tanggal dan jam pembacaan awal
                    $tanggalAwal = ExcelHelpers::parseExcelDate($dataRow[1]);
                    $jamAwal = ExcelHelpers::cleanTime($dataRow[2]);
                    if (!$tanggalAwal || !$jamAwal) {
                        $errors[] = "Baris $rowNumber: Format tanggal/jam pembacaan awal tidak valid";
                        $errorCount++;
                        $allDataValid = false; // Tandai bahwa tidak semua data valid
                        continue;
                    }
                    $waktuAwal = $tanggalAwal . ' ' . $jamAwal;

                    // Parse tanggal dan jam pembacaan akhir
                    $tanggalAkhir = ExcelHelpers::parseExcelDate($dataRow[4]);
                    $jamAkhir = ExcelHelpers::cleanTime($dataRow[5]);
                    if (!$tanggalAkhir || !$jamAkhir) {
                        $errors[] = "Baris $rowNumber: Format tanggal/jam pembacaan akhir tidak valid";
                        $errorCount++;
                        $allDataValid = false; // Tandai bahwa tidak semua data valid
                        continue;
                    }
                    $waktuAkhir = $tanggalAkhir . ' ' . $jamAkhir;

                    // Parse dan bersihkan volume meter
                    $volumeAwal = ExcelHelpers::parseExcelNumber($dataRow[3]);
                    $volumeAkhir = ExcelHelpers::parseExcelNumber($dataRow[6]);
                    if ($volumeAwal === null || $volumeAkhir === null) {
                        $errors[] = "Baris $rowNumber: Format volume meter tidak valid";
                        $errorCount++;
                        $allDataValid = false; // Tandai bahwa tidak semua data valid
                        continue;
                    }

                    // Hitung volume flow meter
                    $volumeFlowMeter = $volumeAkhir - $volumeAwal;

                    // Validasi volume (jika tidak di-skip)
                    if (!$skipValidation) {
                        // Cek apakah waktu awal lebih baru dari waktu akhir
                        // Pembacaan dengan waktu yang sama diperbolehkan
                        if (strtotime($waktuAwal) > strtotime($waktuAkhir)) {
                            $errors[] = "Baris $rowNumber: Waktu pembacaan awal tidak boleh lebih besar dari waktu pembacaan akhir";
                            $errorCount++;
                            $allDataValid = false; // Tandai bahwa tidak semua data valid
                            continue;
                        }

                        // Cek volume flow meter (volume akhir harus >= volume awal)
                        if ($volumeFlowMeter < 0) {
                            $errors[] = "Baris $rowNumber: Volume akhir harus lebih besar atau sama dengan volume awal";
                            $errorCount++;
                            $allDataValid = false; // Tandai bahwa tidak semua data valid
                            continue;
                        }

                        // Cek kontinyuitas antar baris
                        if ($i > $dataStartRow) {
                            $prevRow = $rows[$i - 1];
                            $prevVolumeAkhir = ExcelHelpers::parseExcelNumber($prevRow[6]);
                            $tolerance = 0.01; // Toleransi untuk perbedaan angka floating point

                            if ($prevVolumeAkhir !== null && abs($prevVolumeAkhir - $volumeAwal) > $tolerance) {
                                $errors[] = "Baris $rowNumber: Volume awal ($volumeAwal) tidak sesuai dengan volume akhir dari baris sebelumnya ($prevVolumeAkhir)";
                                $errorCount++;
                                $allDataValid = false; // Tandai bahwa tidak semua data valid
                                continue;
                            }
                        }
                    }

                    // Data sudah valid, siapkan untuk disimpan
                    $dataInput = [
                        'pembacaan_awal' => [
                            'waktu' => $waktuAwal,
                            'volume' => $volumeAwal
                        ],
                        'pembacaan_akhir' => [
                            'waktu' => $waktuAkhir,
                            'volume' => $volumeAkhir
                        ],
                        'volume_flow_meter' => $volumeFlowMeter
                    ];

                    // Simpan data pencatatan
                    $dataPencatatan = new DataPencatatan();
                    $dataPencatatan->customer_id = $customer->id;
                    $dataPencatatan->data_input = json_encode($dataInput);
                    $dataPencatatan->nama_customer = $customer->name;
                    $dataPencatatan->status_pembayaran = 'belum_lunas';

                    // Hitung harga otomatis
                    $dataPencatatan->hitungHarga();

                    $dataPencatatan->save();

                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($i + 1) . ": " . $e->getMessage();
                    $errorCount++;
                    $allDataValid = false; // Tandai bahwa tidak semua data valid
                    Log::error('Error importing Excel row ' . ($i + 1) . ': ' . $e->getMessage(), [
                        'file' => $file->getClientOriginalName(),
                        'customer_id' => $customer->id,
                        'row_data' => $rows[$i] ?? null,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Setelah selesai memproses semua baris, cek apakah semua data valid
            if (!$allDataValid) {
                // Jika ada data yang tidak valid, rollback transaksi
                DB::rollBack();

                // Simpan errors ke session untuk ditampilkan di view
                session()->flash('import_errors', $errors);

                return redirect()->route('data-pencatatan.customer-detail', $customer->id)
                    ->with('error', "Gagal mengimport data. Terdapat $errorCount error. Harap perbaiki file Excel dan coba lagi.");
            }

            // Jika ada data yang berhasil diimport dan semua data valid
            if ($importedCount > 0 && $allDataValid) {
                // Commit transaksi database
                DB::commit();

                // Rekalkulasi total pembelian customer
                app(UserController::class)->rekalkulasiTotalPembelian($customer);
                // Update saldo bulanan customer
                // Ambil tanggal paling awal dari data yang diimport untuk menentukan bulan mulai
                $earliestDate = null;
                $dataPencatatans = $customer->dataPencatatan()->orderBy('created_at')->get();
                foreach ($dataPencatatans as $data) {
                    $dataInput = $this->ensureArray($data->data_input);
                    if (!empty($dataInput) && !empty($dataInput['pembacaan_awal']['waktu'])) {
                        $recordDate = Carbon::parse($dataInput['pembacaan_awal']['waktu']);
                        if ($earliestDate === null || $recordDate < $earliestDate) {
                            $earliestDate = $recordDate;
                        }
                    }
                }

                // Jika ada data, update saldo bulanan mulai dari bulan data paling awal
                if ($earliestDate) {
                    $startMonth = $earliestDate->format('Y-m');
                    $customer->updateMonthlyBalances($startMonth);
                }
                $successMessage = "Berhasil mengimport $importedCount data pencatatan.";

                // Jika ada error, tambahkan ke pesan
                if ($errorCount > 0) {
                    // Simpan errors ke session untuk ditampilkan di view
                    session()->flash('import_errors', $errors);
                    return redirect()->route('data-pencatatan.customer-detail', $customer->id)
                        ->with('warning', "$successMessage Terdapat $errorCount baris yang tidak dapat diimport. Lihat detail error.");
                }

                return redirect()->route('data-pencatatan.customer-detail', $customer->id)
                    ->with('success', $successMessage);
            } else {
                // Rollback transaksi jika tidak ada data yang berhasil diimport
                DB::rollBack();

                // Jika ada error, tampilkan pesan error
                if ($errorCount > 0) {
                    session()->flash('import_errors', $errors);
                    return redirect()->route('data-pencatatan.customer-detail', $customer->id)
                        ->with('error', "Gagal mengimport data. Tidak ada data valid yang ditemukan. Terdapat $errorCount error.");
                }

                // Jika tidak ada error tapi juga tidak ada data yang diimport (misalnya file kosong)
                return redirect()->route('data-pencatatan.customer-detail', $customer->id)
                    ->with('error', "Gagal mengimport data. Tidak ada data yang ditemukan dalam file Excel.");
            }
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error importing Excel file: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'customer_id' => $customer->id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('data-pencatatan.customer-detail', $customer->id)
                ->with('error', 'Gagal mengimport file Excel: ' . $e->getMessage());
        }
    }

    /**
     * Download template Excel untuk import data pencatatan
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplateExcel()
    {
        try {
            // Log attempt to download template
            \Log::info('Attempting to download Excel template');

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
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_');

            // Log temp file path
            \Log::info('Created temp file', ['path' => $tempFile]);

            $writer->save($tempFile);

            // Log successful save
            \Log::info('Successfully saved Excel template to temp file');

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Log error
            \Log::error('Error creating Excel template: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Untuk debugging, tampilkan error langsung ke user
            return response()->json([
                'error' => 'Gagal membuat template Excel: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

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
}
