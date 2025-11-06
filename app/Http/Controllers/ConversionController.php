<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\TextConverterService;
use App\Services\DocxConverterService;
use App\Services\XlsxConverterService;
use ZipArchive;

class ConversionController extends Controller
{
    private TextConverterService $textConverter;
    private DocxConverterService $docxConverter;
    private XlsxConverterService $xlsxConverter;

    public function __construct(
        TextConverterService $textConverter,
        DocxConverterService $docxConverter,
        XlsxConverterService $xlsxConverter
    ) {
        $this->textConverter = $textConverter;
        $this->docxConverter = $docxConverter;
        $this->xlsxConverter = $xlsxConverter;
    }

    /**
     * Prikazuje glavnu stranicu aplikacije
     */
    public function index()
    {
        return view('converter');
    }

    /**
     * Konvertuje tekst između latinice i ćirilice
     */
    public function convertText(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'direction' => 'required|in:cirilica,latinica'
        ]);

        $text = $request->input('text');
        $direction = $request->input('direction');

        $result = $direction === 'cirilica'
            ? $this->textConverter->convertToCirilica($text)
            : $this->textConverter->convertToLatinica($text);

        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    }

    /**
     * Konvertuje DOCX fajl(ove)
     */
    public function convertDocx(Request $request)
    {
        $request->validate([
            'direction' => 'required|in:cirilica,latinica',
            'files' => 'required|array',
            'files.*' => 'file|mimes:docx|max:10240' // max 10MB
        ]);

        $direction = $request->input('direction');
        $toCirilica = $direction === 'cirilica';
        $files = $request->file('files');

        $uploadedPaths = [];
        $outputPaths = [];
        $failed = [];

        // Upload fajlova
        foreach ($files as $file) {
            $path = $file->store('uploads/docx', 'local');
            $uploadedPaths[] = storage_path('app/' . $path);
        }

        // Konverzija
        foreach ($uploadedPaths as $filePath) {
            $result = $this->docxConverter->convertFile($filePath, $toCirilica);

            if ($result['success'] && $result['output_path']) {
                $outputPaths[] = $result['output_path'];
            } else {
                $failed[] = [
                    'file' => basename($filePath),
                    'error' => $result['error']
                ];
            }
        }

        // Ako je samo jedan fajl, vrati direktno
        if (count($outputPaths) === 1) {
            return response()->download($outputPaths[0])->deleteFileAfterSend(true);
        }

        // Ako ima više fajlova, napravi ZIP arhivu
        if (count($outputPaths) > 1) {
            $zipPath = storage_path('app/uploads/converted_' . time() . '.zip');
            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                foreach ($outputPaths as $filePath) {
                    $zip->addFile($filePath, basename($filePath));
                }
                $zip->close();

                // Obriši pojedinačne fajlove
                foreach (array_merge($uploadedPaths, $outputPaths) as $path) {
                    @unlink($path);
                }

                return response()->download($zipPath)->deleteFileAfterSend(true);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Konverzija nije uspela',
            'failed' => $failed
        ], 500);
    }

    /**
     * Dobija zaglavlja iz XLSX fajla
     */
    public function getXlsxHeaders(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240'
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads/xlsx', 'local');
        $filePath = storage_path('app/' . $path);

        $result = $this->xlsxConverter->getHeaders($filePath);

        // Obriši privremeni fajl
        @unlink($filePath);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'headers' => $result['headers']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error']
        ], 500);
    }

    /**
     * Konvertuje XLSX fajl(ove)
     */
    public function convertXlsx(Request $request)
    {
        $request->validate([
            'direction' => 'required|in:cirilica,latinica',
            'files' => 'required|array',
            'files.*' => 'file|mimes:xlsx|max:10240', // max 10MB
            'skip_columns' => 'nullable|array'
        ]);

        $direction = $request->input('direction');
        $toCirilica = $direction === 'cirilica';
        $files = $request->file('files');
        $skipColumns = $request->input('skip_columns', []);

        $filesData = [];
        $outputPaths = [];
        $failed = [];

        // Upload fajlova i priprema podataka
        foreach ($files as $index => $file) {
            $path = $file->store('uploads/xlsx', 'local');
            $filePath = storage_path('app/' . $path);

            // Dobij kolone za preskakanje za ovaj fajl
            $fileSkipColumns = $skipColumns[$index] ?? [];

            $filesData[] = [
                'path' => $filePath,
                'skip_columns' => $fileSkipColumns
            ];
        }

        // Konverzija
        foreach ($filesData as $fileData) {
            $result = $this->xlsxConverter->convertFile(
                $fileData['path'],
                $toCirilica,
                $fileData['skip_columns']
            );

            if ($result['success'] && $result['output_path']) {
                $outputPaths[] = $result['output_path'];
            } else {
                $failed[] = [
                    'file' => basename($fileData['path']),
                    'error' => $result['error']
                ];
            }

            // Obriši originalni upload
            @unlink($fileData['path']);
        }

        // Ako je samo jedan fajl, vrati direktno
        if (count($outputPaths) === 1) {
            return response()->download($outputPaths[0])->deleteFileAfterSend(true);
        }

        // Ako ima više fajlova, napravi ZIP arhivu
        if (count($outputPaths) > 1) {
            $zipPath = storage_path('app/uploads/converted_' . time() . '.zip');
            $zip = new ZipArchive();

            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                foreach ($outputPaths as $filePath) {
                    $zip->addFile($filePath, basename($filePath));
                }
                $zip->close();

                // Obriši pojedinačne fajlove
                foreach ($outputPaths as $path) {
                    @unlink($path);
                }

                return response()->download($zipPath)->deleteFileAfterSend(true);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Konverzija nije uspela',
            'failed' => $failed
        ], 500);
    }
}
