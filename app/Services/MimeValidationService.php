<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * SECURITY: MIME Type Validation Service
 *
 * Validates uploaded files using magic bytes (file signatures) to prevent
 * file extension spoofing attacks.
 */
class MimeValidationService
{
    /**
     * Magic bytes for supported file types
     *
     * @var array<string, array>
     */
    private const MAGIC_BYTES = [
        'docx' => [
            'signature' => '504B0304', // ZIP header (DOCX is ZIP-based)
            'offset' => 0,
            'length' => 4,
            'mime_types' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip'
            ]
        ],
        'doc' => [
            'signature' => 'D0CF11E0A1B11AE1', // MS Office binary format
            'offset' => 0,
            'length' => 8,
            'mime_types' => [
                'application/msword',
                'application/vnd.ms-office'
            ]
        ],
        'xlsx' => [
            'signature' => '504B0304', // ZIP header (XLSX is ZIP-based)
            'offset' => 0,
            'length' => 4,
            'mime_types' => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip'
            ]
        ],
        'xls' => [
            'signature' => 'D0CF11E0A1B11AE1', // MS Office binary format
            'offset' => 0,
            'length' => 8,
            'mime_types' => [
                'application/vnd.ms-excel',
                'application/vnd.ms-office'
            ]
        ]
    ];

    /**
     * Validates if uploaded file matches expected file type using magic bytes
     *
     * @param UploadedFile $file The uploaded file
     * @param array $allowedExtensions Allowed file extensions (e.g., ['docx', 'doc'])
     * @return array ['valid' => bool, 'error' => string|null, 'detected_type' => string|null]
     */
    public function validate(UploadedFile $file, array $allowedExtensions): array
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return [
                'valid' => false,
                'error' => 'Invalid file upload: ' . $file->getErrorMessage(),
                'detected_type' => null
            ];
        }

        // Get file extension from client
        $clientExtension = strtolower($file->getClientOriginalExtension());

        // Check if extension is in allowed list
        if (!in_array($clientExtension, $allowedExtensions)) {
            return [
                'valid' => false,
                'error' => "File extension '.{$clientExtension}' is not allowed. Allowed: " . implode(', ', $allowedExtensions),
                'detected_type' => null
            ];
        }

        // Get file path for magic bytes check
        $filePath = $file->getRealPath();

        if (!$filePath || !file_exists($filePath)) {
            return [
                'valid' => false,
                'error' => 'File path not accessible for validation',
                'detected_type' => null
            ];
        }

        // Read magic bytes
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return [
                'valid' => false,
                'error' => 'Unable to read file for validation',
                'detected_type' => null
            ];
        }

        // Check magic bytes for each allowed extension
        $detectedType = null;
        $magicBytesMatch = false;

        foreach ($allowedExtensions as $extension) {
            if (!isset(self::MAGIC_BYTES[$extension])) {
                continue;
            }

            $config = self::MAGIC_BYTES[$extension];

            // Seek to offset
            fseek($handle, $config['offset']);

            // Read bytes
            $bytes = fread($handle, $config['length']);

            if ($bytes === false) {
                continue;
            }

            // Convert to hex
            $hexBytes = strtoupper(bin2hex($bytes));

            // Check if signature matches
            if ($hexBytes === $config['signature']) {
                $detectedType = $extension;
                $magicBytesMatch = true;
                break;
            }
        }

        fclose($handle);

        // Verify magic bytes match
        if (!$magicBytesMatch) {
            return [
                'valid' => false,
                'error' => "File content does not match expected format. File may be corrupted or has wrong extension.",
                'detected_type' => $detectedType
            ];
        }

        // Additional check: verify MIME type from system
        $mimeType = $file->getMimeType();
        $expectedMimeTypes = self::MAGIC_BYTES[$detectedType]['mime_types'];

        if (!in_array($mimeType, $expectedMimeTypes)) {
            // Log warning but don't fail - some systems report MIME types differently
            \Log::warning('MIME type mismatch', [
                'detected_type' => $detectedType,
                'system_mime' => $mimeType,
                'expected_mimes' => $expectedMimeTypes,
                'filename' => $file->getClientOriginalName()
            ]);
        }

        return [
            'valid' => true,
            'error' => null,
            'detected_type' => $detectedType
        ];
    }

    /**
     * Quick validation for single file type
     *
     * @param UploadedFile $file
     * @param string $expectedExtension
     * @return bool
     */
    public function validateSingle(UploadedFile $file, string $expectedExtension): bool
    {
        $result = $this->validate($file, [$expectedExtension]);
        return $result['valid'];
    }

    /**
     * Get human-readable error message for validation result
     *
     * @param array $validationResult Result from validate() method
     * @return string
     */
    public function getErrorMessage(array $validationResult): string
    {
        if ($validationResult['valid']) {
            return '';
        }

        return $validationResult['error'] ?? 'File validation failed';
    }
}
