<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Exception;

/**
 * Read Filter za učitavanje samo prvog reda Excel fajla
 * Ovo drastično smanjuje memorijsku potrošnju
 */
class FirstRowFilter implements IReadFilter
{
    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        // Učitaj samo prvi red
        return $row === 1;
    }
}

class XlsxConverterService
{
    private TextConverterService $textConverter;

    public function __construct(TextConverterService $textConverter)
    {
        $this->textConverter = $textConverter;
    }

    /**
     * Dobija zaglavlja (prvi red) iz Excel fajla
     * Koristi ReadFilter da učita SAMO prvi red, ne ceo fajl
     * Ovo drastično smanjuje memorijsku potrošnju
     *
     * @param string $filePath Putanja do Excel fajla
     * @return array ['success' => bool, 'headers' => array|null, 'error' => string|null]
     */
    public function getHeaders(string $filePath): array
    {
        try {
            // Kreiraj reader sa filterom koji čita samo prvi red
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $reader->setReadFilter(new FirstRowFilter());

            // Učitaj samo prvi red (DRASTIČNO manje memorije!)
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $headers = [];
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
                if (!empty($cellValue)) {
                    $columnLetter = Coordinate::stringFromColumnIndex($col);
                    $headers[] = [
                        'column' => $columnLetter,
                        'name' => $cellValue
                    ];
                }
            }

            // Oslobodi memoriju
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();

            return [
                'success' => true,
                'headers' => $headers,
                'error' => null
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'headers' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Konvertuje XLSX fajl u latinicu ili ćirilicu
     *
     * @param string $filePath Putanja do originalnog fajla
     * @param bool $toCirilica True za konverziju u ćirilicu, false za latinicu
     * @param array $columnsToSkip Nazivi zaglavlja kolona koje treba preskočiti
     * @return array ['success' => bool, 'output_path' => string|null, 'error' => string|null]
     */
    public function convertFile(string $filePath, bool $toCirilica, array $columnsToSkip = []): array
    {
        try {
            // Generiši naziv izlaznog fajla
            $suffix = $toCirilica ? '_cirilica' : '_latinica';
            $directory = dirname($filePath);
            $baseName = pathinfo($filePath, PATHINFO_FILENAME);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $outputPath = $directory . '/' . $baseName . $suffix . '.' . $extension;

            // Kopiraj originalni fajl
            if (!copy($filePath, $outputPath)) {
                throw new Exception('Failed to copy file');
            }

            // Učitaj Excel fajl
            $spreadsheet = IOFactory::load($outputPath);

            // Obradi sve worksheet-ove
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                // Napravi mapu zaglavlja: kolona => naziv zaglavlja
                $headers = [];
                $highestColumn = $worksheet->getHighestColumn();
                $highestRow = $worksheet->getHighestRow();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                // Učitaj zaglavlja iz prvog reda
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellValue = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
                    if (!empty($cellValue)) {
                        $columnLetter = Coordinate::stringFromColumnIndex($col);
                        $headers[$columnLetter] = trim($cellValue);
                    }
                }

                // Obradi sve ćelije
                for ($row = 1; $row <= $highestRow; $row++) {
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $columnLetter = Coordinate::stringFromColumnIndex($col);

                        // Proveri da li treba preskočiti ovu kolonu
                        if (isset($headers[$columnLetter]) && in_array($headers[$columnLetter], $columnsToSkip)) {
                            continue;
                        }

                        $cell = $worksheet->getCellByColumnAndRow($col, $row);

                        // Preskoči ćelije sa formulom
                        if ($cell->isFormula()) {
                            continue;
                        }

                        $cellValue = $cell->getValue();

                        if (!empty($cellValue) && is_string($cellValue)) {
                            $converted = $toCirilica
                                ? $this->textConverter->convertToCirilica($cellValue)
                                : $this->textConverter->convertToLatinica($cellValue);
                            $cell->setValue($converted);
                        }
                    }
                }
            }

            // Snimi fajl
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($outputPath);

            return [
                'success' => true,
                'output_path' => $outputPath,
                'error' => null
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'output_path' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Batch konverzija više fajlova
     *
     * @param array $files Niz fajlova sa putanjama i kolonama za preskakanje
     *                     Format: [['path' => '...', 'skip_columns' => [...]], ...]
     * @param bool $toCirilica True za konverziju u ćirilicu, false za latinicu
     * @return array ['total' => int, 'success' => int, 'failed' => array]
     */
    public function convertMultipleFiles(array $files, bool $toCirilica): array
    {
        $total = count($files);
        $successCount = 0;
        $failed = [];

        foreach ($files as $fileData) {
            $filePath = $fileData['path'] ?? null;
            $skipColumns = $fileData['skip_columns'] ?? [];

            if (!$filePath) {
                continue;
            }

            $result = $this->convertFile($filePath, $toCirilica, $skipColumns);

            if ($result['success']) {
                $successCount++;
            } else {
                $failed[] = [
                    'file' => basename($filePath),
                    'error' => $result['error']
                ];
            }
        }

        return [
            'total' => $total,
            'success' => $successCount,
            'failed' => $failed
        ];
    }
}
