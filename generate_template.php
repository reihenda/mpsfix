<?php

// Load library PhpSpreadsheet (memastikan aplikasi Laravel sudah menginstalnya)
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => 'D9EAD3',
        ],
    ],
];

// Style untuk baris data
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
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

// Simpan file di direktori public/templates
$writer = new Xlsx($spreadsheet);
$filename = __DIR__ . '/public/templates/template_data_pencatatan.xlsx';
$writer->save($filename);

echo "Template Excel berhasil dibuat dan disimpan di: $filename";
