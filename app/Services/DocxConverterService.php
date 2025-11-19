<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use ZipArchive;
use DOMDocument;
use DOMXPath;

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

            // DOCX je ZIP arhiva - manipulišemo direktno bez PHPWord Writer-a
            // da избежамо проблеме са сликама
            $zip = new \ZipArchive();

            if ($zip->open($outputPath) !== true) {
                throw new Exception('Failed to open DOCX as ZIP archive');
            }

            // Ekstrakcija i konverzija document.xml (glavni tekst)
            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml === false) {
                throw new Exception('Failed to extract document.xml from DOCX');
            }

            // Konvertuj tekst unutar XML-a (čuvajući XML strukturu)
            $convertedXml = $this->convertXmlContent($documentXml, $toCirilica);

            // Zameni document.xml sa konvertovanim
            $zip->deleteName('word/document.xml');
            $zip->addFromString('word/document.xml', $convertedXml);

            // Konvertuj headers ako postoje
            for ($i = 1; $i <= 10; $i++) {
                $headerXml = $zip->getFromName("word/header{$i}.xml");
                if ($headerXml !== false) {
                    $convertedHeader = $this->convertXmlContent($headerXml, $toCirilica);
                    $zip->deleteName("word/header{$i}.xml");
                    $zip->addFromString("word/header{$i}.xml", $convertedHeader);
                }
            }

            // Konvertuj footers ako postoje
            for ($i = 1; $i <= 10; $i++) {
                $footerXml = $zip->getFromName("word/footer{$i}.xml");
                if ($footerXml !== false) {
                    $convertedFooter = $this->convertXmlContent($footerXml, $toCirilica);
                    $zip->deleteName("word/footer{$i}.xml");
                    $zip->addFromString("word/footer{$i}.xml", $convertedFooter);
                }
            }

            $zip->close();

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
     * Konvertuje tekstualni sadržaj unutar XML-a, čuvajući XML tagove
     *
     * SECURITY: Protected against XXE (XML External Entity) injection attacks
     */
    private function convertXmlContent(string $xml, bool $toCirilica): string
    {
        // Učitaj XML
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        // SECURITY: XXE Protection
        // Disable external entity loading to prevent XXE attacks
        $previousEntityLoader = libxml_disable_entity_loader(true);

        // Suppress warnings za malformed XML
        $previousErrorLevel = libxml_use_internal_errors(true);

        // Load XML with security flags:
        // LIBXML_NONET - Disable network access
        // LIBXML_DTDLOAD - Load external DTD
        // LIBXML_DTDATTR - Default DTD attributes
        $dom->loadXML($xml, LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_DTDATTR);

        // Restore previous settings
        libxml_use_internal_errors($previousErrorLevel);
        libxml_disable_entity_loader($previousEntityLoader);

        // Pronađi sve <w:t> tagove (Word text nodes)
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $textNodes = $xpath->query('//w:t');

        foreach ($textNodes as $node) {
            $originalText = $node->nodeValue;
            if (!empty($originalText)) {
                $convertedText = $toCirilica
                    ? $this->textConverter->convertToCirilica($originalText)
                    : $this->textConverter->convertToLatinica($originalText);
                $node->nodeValue = $convertedText;
            }
        }

        return $dom->saveXML();
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
