<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Glavna stranica
Route::get('/', [ConversionController::class, 'index'])->name('home');

// API rute za konverziju
Route::post('/convert/text', [ConversionController::class, 'convertText'])->name('convert.text');
Route::post('/convert/docx', [ConversionController::class, 'convertDocx'])->name('convert.docx');
Route::post('/convert/xlsx', [ConversionController::class, 'convertXlsx'])->name('convert.xlsx');
Route::post('/xlsx/headers', [ConversionController::class, 'getXlsxHeaders'])->name('xlsx.headers');

// Debug route
Route::get('/debug', function() {
    return response()->json([
        'phpword_exists' => class_exists('\PhpOffice\PhpWord\IOFactory'),
        'phpspreadsheet_exists' => class_exists('\PhpOffice\PhpSpreadsheet\IOFactory'),
        'zip_enabled' => extension_loaded('zip'),
        'xml_enabled' => extension_loaded('xml'),
        'gd_enabled' => extension_loaded('gd'),
        'storage_writable' => is_writable(storage_path('app/uploads')),
        'storage_path' => storage_path('app/uploads'),
        'php_upload_max_filesize' => ini_get('upload_max_filesize'),
        'php_post_max_size' => ini_get('post_max_size'),
        'php_memory_limit' => ini_get('memory_limit'),
        'php_max_file_uploads' => ini_get('max_file_uploads'),
    ]);
});
