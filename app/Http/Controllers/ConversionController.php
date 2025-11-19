<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\TextConverterService;
use App\Services\DocxConverterService;
use App\Services\XlsxConverterService;
use App\Services\MimeValidationService;
use ZipArchive;
use Exception;

class ConversionController extends Controller
{
    private TextConverterService $textConverter;
    private DocxConverterService $docxConverter;
    private XlsxConverterService $xlsxConverter;
    private MimeValidationService $mimeValidator;

    public function __construct(
        TextConverterService $textConverter,
        DocxConverterService $docxConverter,
        XlsxConverterService $xlsxConverter,
        MimeValidationService $mimeValidator
    ) {
        $this->textConverter = $textConverter;
        $this->docxConverter = $docxConverter;
        $this->xlsxConverter = $xlsxConverter;
        $this->mimeValidator = $mimeValidator;
    }

    /**
     * Generates secure filename using UUID to prevent:
     * - Path traversal attacks
     * - Race conditions (file overwrite)
     * - Filename collisions
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    private function generateSecureFilename($file): string
    {
        // Get extension from uploaded file
        $extension = $file->getClientOriginalExtension();

        // Sanitize extension (only alphanumeric)
        $safeExtension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);

        // Generate UUID v4 for guaranteed uniqueness
        $uuid = Str::uuid()->toString();

        return "{$uuid}.{$safeExtension}";
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
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Konvertuje DOCX fajl(ove)
     */
    public function convertDocx(Request $request)
    {
        try {
            $request->validate([
                'direction' => 'required|in:cirilica,latinica',
                'files' => 'required|array',
                'files.*' => 'file|mimes:docx,doc|max:20480' // max 20MB
            ]);

            $direction = $request->input('direction');
            $toCirilica = $direction === 'cirilica';
            $files = $request->file('files');

            $uploadedPaths = [];
            $outputPaths = [];
            $failed = [];

            // SECURITY: Validate files using magic bytes before upload
            foreach ($files as $index => $file) {
                $validation = $this->mimeValidator->validate($file, ['docx', 'doc']);

                if (!$validation['valid']) {
                    $failed[] = [
                        'file' => 'File ' . ($index + 1),
                        'error' => $validation['error']
                    ];
                }
            }

            // If all files failed validation, return error
            if (count($failed) === count($files)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All files failed validation',
                    'failed' => $failed
                ], 400);
            }

            // Upload fajlova (only validated ones)
            foreach ($files as $index => $file) {
                // Re-validate (to skip failed files from previous loop)
                $validation = $this->mimeValidator->validate($file, ['docx', 'doc']);

                if (!$validation['valid']) {
                    continue; // Skip invalid files
                }

                $filename = $this->generateSecureFilename($file);
                $path = $file->storeAs('uploads/docx', $filename);
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
                        'error' => $result['error'] ?? 'Unknown error'
                    ];
                }
            }

            // Ako nema uspešnih konverzija
            if (empty($outputPaths)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nijedan fajl nije konvertovan',
                    'failed' => $failed
                ], 500);
            }

            // Ako je samo jedan fajl, vrati direktno
            if (count($outputPaths) === 1 && file_exists($outputPaths[0])) {
                $response = response()->download($outputPaths[0], basename($outputPaths[0]));

                // Cleanup nakon download-a
                register_shutdown_function(function() use ($uploadedPaths, $outputPaths) {
                    foreach (array_merge($uploadedPaths, $outputPaths) as $path) {
                        if (file_exists($path)) @unlink($path);
                    }
                });

                return $response;
            }

            // Ako ima više fajlova, napravi ZIP arhivu
            if (count($outputPaths) > 1) {
                $zipPath = storage_path('app/uploads/converted_' . time() . '.zip');
                $zip = new ZipArchive();

                if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                    foreach ($outputPaths as $filePath) {
                        if (file_exists($filePath)) {
                            $zip->addFile($filePath, basename($filePath));
                        }
                    }
                    $zip->close();

                    $response = response()->download($zipPath);

                    // Cleanup nakon download-a
                    register_shutdown_function(function() use ($uploadedPaths, $outputPaths, $zipPath) {
                        foreach (array_merge($uploadedPaths, $outputPaths) as $path) {
                            if (file_exists($path)) @unlink($path);
                        }
                        if (file_exists($zipPath)) @unlink($zipPath);
                    });

                    return $response;
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Konverzija nije uspela',
                'failed' => $failed
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            // SECURITY: Generate unique error ID for tracking
            $errorId = Str::uuid()->toString();

            // Log detailed error server-side
            Log::error('DOCX Conversion Error', [
                'error_id' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            // SECURITY: Return safe error message to client (no stack trace!)
            return response()->json([
                'success' => false,
                'error' => 'An error occurred during conversion. Please try again.',
                'error_id' => $errorId // For support/debugging reference
            ], 500);
        }
    }

    /**
     * Dobija zaglavlja iz XLSX fajla
     */
    public function getXlsxHeaders(Request $request)
    {
        // Force JSON response for all errors
        $request->headers->set('Accept', 'application/json');

        try {
            // Manual validation with better error handling
            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'error' => 'No file uploaded'
                ], 400);
            }

            $file = $request->file('file');

            // Check file is valid
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid file upload: ' . $file->getErrorMessage()
                ], 400);
            }

            // SECURITY: Validate file using magic bytes
            $validation = $this->mimeValidator->validate($file, ['xlsx', 'xls']);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => $validation['error']
                ], 400);
            }

            // Check file size (20MB = 20971520 bytes)
            if ($file->getSize() > 20971520) {
                return response()->json([
                    'success' => false,
                    'error' => 'File too large. Maximum size is 20MB.'
                ], 400);
            }

            // Generate secure filename using UUID
            $filename = $this->generateSecureFilename($file);

            // Store the file
            $path = $file->storeAs('uploads/xlsx', $filename);

            if (!$path) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to store uploaded file'
                ], 500);
            }

            $filePath = storage_path('app/' . $path);

            // Check file exists
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Uploaded file not found after storage'
                ], 500);
            }

            // Get headers from the file
            $result = $this->xlsxConverter->getHeaders($filePath);

            // Clean up temp file
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'headers' => $result['headers'] ?? []
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Unknown error occurred'
            ], 500);

        } catch (\Throwable $e) {
            // SECURITY: Generate unique error ID for tracking
            $errorId = Str::uuid()->toString();

            // Catch all errors including fatal errors and log server-side
            Log::error('XLSX Headers Error', [
                'error_id' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            // SECURITY: Return safe error message to client (no stack trace!)
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while reading file headers. Please try again.',
                'error_id' => $errorId // For support/debugging reference
            ], 500);
        }
    }

    /**
     * Konvertuje XLSX fajl(ove)
     */
    public function convertXlsx(Request $request)
    {
        try {
            // Povećaj execution time limit za velike fajlove (5 minuta)
            @set_time_limit(300);

            $request->validate([
                'direction' => 'required|in:cirilica,latinica',
                'files' => 'required|array',
                'files.*' => 'file|mimes:xlsx,xls|max:20480', // max 20MB
                'skip_columns' => 'nullable|array',
                'skip_columns.*' => 'nullable|string'
            ]);

            $direction = $request->input('direction');
            $toCirilica = $direction === 'cirilica';
            $files = $request->file('files');

            // Parse skip_columns from JSON string to array
            $skipColumnsRaw = $request->input('skip_columns', '[]');
            $skipColumns = [];
            if (is_string($skipColumnsRaw)) {
                $skipColumns = json_decode($skipColumnsRaw, true) ?? [];
            } elseif (is_array($skipColumnsRaw)) {
                $skipColumns = $skipColumnsRaw;
            }

            $filesData = [];
            $outputPaths = [];
            $failed = [];

            // SECURITY: Validate files using magic bytes before upload
            $validFiles = [];
            foreach ($files as $index => $file) {
                $validation = $this->mimeValidator->validate($file, ['xlsx', 'xls']);
                $originalName = basename($file->getClientOriginalName()); // For error messages only

                if (!$validation['valid']) {
                    $failed[] = [
                        'file' => $originalName,
                        'error' => $validation['error']
                    ];
                } else {
                    $validFiles[$index] = $file;
                }
            }

            // If all files failed validation, return error
            if (empty($validFiles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All files failed validation',
                    'failed' => $failed
                ], 400);
            }

            // Upload fajlova i priprema podataka (only validated ones)
            foreach ($validFiles as $index => $file) {
                $filename = $this->generateSecureFilename($file);
                $originalName = basename($file->getClientOriginalName()); // For error messages only
                $path = $file->storeAs('uploads/xlsx', $filename);

                if (!$path) {
                    $failed[] = [
                        'file' => $originalName,
                        'error' => 'Failed to store file'
                    ];
                    continue;
                }

                $filePath = storage_path('app/' . $path);

                // Dobij kolone za preskakanje - default je prazna lista
                $fileSkipColumns = [];
                if (isset($skipColumns[$index])) {
                    if (is_string($skipColumns[$index])) {
                        $fileSkipColumns = json_decode($skipColumns[$index], true) ?? [];
                    } elseif (is_array($skipColumns[$index])) {
                        $fileSkipColumns = $skipColumns[$index];
                    }
                }

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
                        'error' => $result['error'] ?? 'Unknown error'
                    ];
                }

                // Obriši originalni upload
                if (file_exists($fileData['path'])) {
                    @unlink($fileData['path']);
                }
            }

            // Ako nema uspešnih konverzija
            if (empty($outputPaths)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nijedan fajl nije konvertovan',
                    'failed' => $failed
                ], 500);
            }

            // Ako je samo jedan fajl, vrati direktno
            if (count($outputPaths) === 1 && file_exists($outputPaths[0])) {
                $response = response()->download($outputPaths[0], basename($outputPaths[0]));

                register_shutdown_function(function() use ($outputPaths) {
                    foreach ($outputPaths as $path) {
                        if (file_exists($path)) @unlink($path);
                    }
                });

                return $response;
            }

            // Ako ima više fajlova, napravi ZIP arhivu
            if (count($outputPaths) > 1) {
                $zipPath = storage_path('app/uploads/converted_' . time() . '.zip');
                $zip = new ZipArchive();

                if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                    foreach ($outputPaths as $filePath) {
                        if (file_exists($filePath)) {
                            $zip->addFile($filePath, basename($filePath));
                        }
                    }
                    $zip->close();

                    $response = response()->download($zipPath);

                    register_shutdown_function(function() use ($outputPaths, $zipPath) {
                        foreach ($outputPaths as $path) {
                            if (file_exists($path)) @unlink($path);
                        }
                        if (file_exists($zipPath)) @unlink($zipPath);
                    });

                    return $response;
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Konverzija nije uspela',
                'failed' => $failed
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            // SECURITY: Generate unique error ID for tracking
            $errorId = Str::uuid()->toString();

            // Log detailed error server-side
            Log::error('XLSX Conversion Error', [
                'error_id' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            // SECURITY: Return safe error message to client (no stack trace!)
            return response()->json([
                'success' => false,
                'error' => 'An error occurred during conversion. Please try again.',
                'error_id' => $errorId // For support/debugging reference
            ], 500);
        }
    }
}
