<?php

namespace User\Service;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class ExportService implements ServiceInterface
{
    /* @var array */
    protected array $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function exportData($account): string
    {
        // Set file name
        $file = $this->makeFileName();

        // Canonize data
        $exportData = $this->canonizeExportData($account['list']);

        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Write the header data to file
        $columnIndex = 1;
        foreach ($exportData['header'] as $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue($columnLetter . '1', $header);
            $columnIndex++;
        }

        // Write the body data to file
        $rowIndex = 2;
        foreach ($exportData['body'] as $row) {
            $columnIndex = 1;
            foreach ($exportData['header'] as $header) {
                $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
                $sheet->setCellValue($columnLetter . $rowIndex, $row[$header] ?? '');
                $columnIndex++;
            }
            $rowIndex++;
        }

        // Create a new Excel file writer
        switch ($this->config['format']) {
            default:
            case 'csv':
                $writer = new CsvWriter($spreadsheet);
                $writer->save($file);
                break;

            case 'xlsx':

                $writer = new XlsxWriter($spreadsheet);
                $writer->save($file);
                break;

            case 'xls':
                $writer = new XlsWriter($spreadsheet);
                $writer->save($file);
                break;
        }

        return $file;
    }

    public function canonizeExportData($data): array
    {
        $exportData = [
            'header' => [],
            'body'   => [],
        ];

        // Loop through the data array and populate the worksheet
        foreach ($data as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $exportData['header'][$columnIndex] = $columnIndex;
                if ($columnIndex === 'roles') {
                    // ToDo: Add it
                } elseif (is_array($value)) {
                    // ToDo: Add it
                } else {
                    $exportData['body'][$rowIndex][$columnIndex] = $value;
                }
            }
        }

        return $exportData;
    }

    private function makeFileName(): string
    {
        return sprintf('%s/%s-%s.%s', $this->config['file_path'], date('Y-m-d-H-i-s'), rand(1000, 9999), $this->config['format']);
    }
}