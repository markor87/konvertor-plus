<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use Exception;

class DocxConverterService
{
    private TextConverterService $textConverter;

    public function __construct(TextConverterService $textConverter)
    {
        $this->textConverter = $textConverter;
    }

    /**
     * Konvertuje DOCX fajl u latinicu ili ćirilicu
     *
     * @param string $filePath Putanja do originalnog fajla
     * @param bool $toCirilica True za konverziju u ćirilicu, false za latinicu
     * @return array ['success' => bool, 'output_path' => string|null, 'error' => string|null]
     */
    public function convertFile(string $filePath, bool $toCirilica): array
    {
        try {
            // Preskoči privremene fajlove
            if (str_starts_with(basename($filePath), '~$')) {
                return [
                    'success' => true,
                    'output_path' => null,
                    'error' => 'Skipped temporary file'
                ];
            }

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

            // Učitaj dokument
            $phpWord = IOFactory::load($outputPath);

            // Obradi sve sekcije
            foreach ($phpWord->getSections() as $section) {
                $this->processSectionElements($section->getElements(), $toCirilica);
            }

            // Snimi dokument
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
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
     * Rekurzivno obrađuje elemente dokumenta
     */
    private function processSectionElements(array $elements, bool $toCirilica): void
    {
        foreach ($elements as $element) {
            // Obrada običnog teksta
            if ($element instanceof Text) {
                $text = $element->getText();
                if (!empty($text)) {
                    $converted = $toCirilica
                        ? $this->textConverter->convertToCirilica($text)
                        : $this->textConverter->convertToLatinica($text);
                    $element->setText($converted);
                }
            }
            // Obrada TextRun elemenata (formatiran tekst)
            elseif ($element instanceof TextRun) {
                foreach ($element->getElements() as $textElement) {
                    if ($textElement instanceof Text) {
                        $text = $textElement->getText();
                        if (!empty($text)) {
                            $converted = $toCirilica
                                ? $this->textConverter->convertToCirilica($text)
                                : $this->textConverter->convertToLatinica($text);
                            $textElement->setText($converted);
                        }
                    }
                }
            }
            // Obrada tabela
            elseif (method_exists($element, 'getRows')) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $this->processSectionElements($cell->getElements(), $toCirilica);
                    }
                }
            }
        }
    }

    /**
     * Batch konverzija više fajlova
     *
     * @param array $filePaths Niz putanja do fajlova
     * @param bool $toCirilica True za konverziju u ćirilicu, false za latinicu
     * @return array ['total' => int, 'success' => int, 'failed' => array]
     */
    public function convertMultipleFiles(array $filePaths, bool $toCirilica): array
    {
        $total = count($filePaths);
        $successCount = 0;
        $failed = [];

        foreach ($filePaths as $filePath) {
            $result = $this->convertFile($filePath, $toCirilica);

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
