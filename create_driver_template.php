<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Create a new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set the header row
$headers = [
    'Route', 
    'Unit', 
    'NAMA PRAMUDI', 
    'Type', 
    'No KTP', 
    'No KPP', 
    'No KK', 
    'No Rekening', 
    'Telepon', 
    'Email', 
    'Status'
];

// Set column headers
foreach ($headers as $index => $header) {
    $column = chr(65 + $index); // Convert to column letter (A, B, C, etc.)
    $sheet->setCellValue($column . '1', $header);
}

// Add example data row
$exampleData = [
    'JAK01',
    'TJ001',
    'Nama Pengemudi',
    'Batangan',
    '1234567890123456',
    'KPP12345',
    '1234567890123456',
    '1234567890123456',
    '081234567890',
    'email@example.com',
    'Aktif'
];

// Set example data
foreach ($exampleData as $index => $value) {
    $column = chr(65 + $index);
    $sheet->setCellValue($column . '2', $value);
}

// Add notes row
$sheet->setCellValue('A3', 'Catatan:');
$sheet->mergeCells('B3:K3');
$sheet->setCellValue('B3', 'Type harus diisi dengan "Batangan" atau "Cadangan". Status harus diisi dengan "Aktif" atau "Nonaktif".');

// Style the header row
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
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
];

$sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

// Style the example data row
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyle('A2:K2')->applyFromArray($dataStyle);

// Style the notes row
$noteStyle = [
    'font' => [
        'italic' => true,
        'color' => ['rgb' => '808080'],
    ],
];
$sheet->getStyle('A3:K3')->applyFromArray($noteStyle);

// Auto-size columns
foreach (range('A', 'K') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Create the template directory if it doesn't exist
if (!is_dir(__DIR__ . '/public/templates')) {
    mkdir(__DIR__ . '/public/templates', 0755, true);
}

// Save the spreadsheet as an Excel file
$writer = new Xlsx($spreadsheet);
$writer->save(__DIR__ . '/public/templates/driver_import_template.xlsx');

echo "Template file created successfully at: public/templates/driver_import_template.xlsx\n";
