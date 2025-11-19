<?php

declare(strict_types=1);

namespace App\Services;

use ZipArchive;
use Illuminate\Support\Facades\Log;

/**
 * SECURITY: ZIP Bomb Protection Service
 *
 * Detects and prevents decompression bomb attacks in ZIP-based files
 * (DOCX, XLSX are ZIP archives).
 *
 * A ZIP bomb is a malicious archive that expands to enormous size when
 * decompressed, causing memory exhaustion and system crashes.
 */
class ZipBombProtectionService
{
    /**
     * Maximum allowed compression ratio (compressed:uncompressed)
     * Typical legitimate files: 10:1 to 100:1
     * ZIP bombs: 1000:1 or higher
     *
     * @var int
     */
    private const MAX_COMPRESSION_RATIO = 200;

    /**
     * Maximum allowed uncompressed size in bytes (500MB)
     *
     * @var int
     */
    private const MAX_UNCOMPRESSED_SIZE = 524288000; // 500 * 1024 * 1024

    /**
     * Maximum number of files in archive to prevent file count attacks
     *
     * @var int
     */
    private const MAX_FILE_COUNT = 10000;

    /**
     * Maximum file name length to prevent path traversal
     *
     * @var int
     */
    private const MAX_FILENAME_LENGTH = 255;

    /**
     * Validates ZIP file against decompression bomb attacks
     *
     * @param string $filePath Path to ZIP file
     * @return array ['safe' => bool, 'error' => string|null, 'stats' => array|null]
     */
    public function validate(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'safe' => false,
                'error' => 'File does not exist',
                'stats' => null
            ];
        }

        $zip = new ZipArchive();
        $result = $zip->open($filePath, ZipArchive::RDONLY);

        if ($result !== true) {
            return [
                'safe' => false,
                'error' => 'Failed to open ZIP archive (corrupted file?)',
                'stats' => null
            ];
        }

        $stats = [
            'file_count' => $zip->numFiles,
            'compressed_size' => filesize($filePath),
            'uncompressed_size' => 0,
            'compression_ratio' => 0,
            'suspicious_files' => []
        ];

        // Check 1: Maximum file count
        if ($zip->numFiles > self::MAX_FILE_COUNT) {
            $zip->close();
            Log::warning('ZIP bomb detected: excessive file count', [
                'file' => basename($filePath),
                'file_count' => $zip->numFiles,
                'max_allowed' => self::MAX_FILE_COUNT
            ]);

            return [
                'safe' => false,
                'error' => "Archive contains too many files ({$zip->numFiles}). Maximum allowed: " . self::MAX_FILE_COUNT,
                'stats' => $stats
            ];
        }

        // Check 2: Calculate total uncompressed size and detect suspicious patterns
        $totalUncompressedSize = 0;
        $suspiciousFiles = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileStats = $zip->statIndex($i);

            if ($fileStats === false) {
                continue;
            }

            // Check filename length (path traversal prevention)
            if (strlen($fileStats['name']) > self::MAX_FILENAME_LENGTH) {
                $suspiciousFiles[] = [
                    'name' => substr($fileStats['name'], 0, 50) . '...',
                    'reason' => 'Filename too long'
                ];
                continue;
            }

            // Check for path traversal attempts
            if (strpos($fileStats['name'], '..') !== false || strpos($fileStats['name'], '\\') !== false) {
                $suspiciousFiles[] = [
                    'name' => $fileStats['name'],
                    'reason' => 'Path traversal attempt detected'
                ];
                continue;
            }

            $uncompressedSize = $fileStats['size'];
            $compressedSize = $fileStats['comp_size'];

            // Calculate per-file compression ratio
            if ($compressedSize > 0) {
                $fileRatio = $uncompressedSize / $compressedSize;

                // Check for individual file with suspicious ratio
                if ($fileRatio > self::MAX_COMPRESSION_RATIO) {
                    $suspiciousFiles[] = [
                        'name' => $fileStats['name'],
                        'reason' => sprintf('Excessive compression ratio: %.0f:1', $fileRatio),
                        'compressed' => $compressedSize,
                        'uncompressed' => $uncompressedSize
                    ];
                }
            }

            $totalUncompressedSize += $uncompressedSize;

            // Early exit if total size exceeds maximum
            if ($totalUncompressedSize > self::MAX_UNCOMPRESSED_SIZE) {
                $zip->close();

                $stats['uncompressed_size'] = $totalUncompressedSize;
                $stats['suspicious_files'] = $suspiciousFiles;

                Log::warning('ZIP bomb detected: excessive uncompressed size', [
                    'file' => basename($filePath),
                    'uncompressed_size' => $totalUncompressedSize,
                    'max_allowed' => self::MAX_UNCOMPRESSED_SIZE
                ]);

                return [
                    'safe' => false,
                    'error' => 'Archive uncompressed size exceeds maximum allowed limit',
                    'stats' => $stats
                ];
            }
        }

        $zip->close();

        // Update stats
        $stats['uncompressed_size'] = $totalUncompressedSize;
        $stats['suspicious_files'] = $suspiciousFiles;

        // Check 3: Overall compression ratio
        $compressedSize = $stats['compressed_size'];
        if ($compressedSize > 0) {
            $overallRatio = $totalUncompressedSize / $compressedSize;
            $stats['compression_ratio'] = $overallRatio;

            if ($overallRatio > self::MAX_COMPRESSION_RATIO) {
                Log::warning('ZIP bomb detected: excessive compression ratio', [
                    'file' => basename($filePath),
                    'ratio' => sprintf('%.0f:1', $overallRatio),
                    'compressed_size' => $compressedSize,
                    'uncompressed_size' => $totalUncompressedSize
                ]);

                return [
                    'safe' => false,
                    'error' => sprintf(
                        'Suspicious compression ratio detected (%.0f:1). Maximum allowed: %d:1',
                        $overallRatio,
                        self::MAX_COMPRESSION_RATIO
                    ),
                    'stats' => $stats
                ];
            }
        }

        // Check 4: Any suspicious files found?
        if (!empty($suspiciousFiles)) {
            Log::warning('ZIP suspicious files detected', [
                'file' => basename($filePath),
                'suspicious_files' => $suspiciousFiles
            ]);

            return [
                'safe' => false,
                'error' => 'Archive contains suspicious files: ' . $suspiciousFiles[0]['reason'],
                'stats' => $stats
            ];
        }

        // All checks passed
        return [
            'safe' => true,
            'error' => null,
            'stats' => $stats
        ];
    }

    /**
     * Quick validation with simplified error message
     *
     * @param string $filePath
     * @return bool
     */
    public function isSafe(string $filePath): bool
    {
        $result = $this->validate($filePath);
        return $result['safe'];
    }

    /**
     * Get configuration values
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'max_compression_ratio' => self::MAX_COMPRESSION_RATIO,
            'max_uncompressed_size' => self::MAX_UNCOMPRESSED_SIZE,
            'max_file_count' => self::MAX_FILE_COUNT,
            'max_filename_length' => self::MAX_FILENAME_LENGTH
        ];
    }
}
