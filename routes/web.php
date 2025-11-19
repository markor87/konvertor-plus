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

// API rute za konverziju - SECURITY: Rate limited to prevent abuse
Route::post('/convert/text', [ConversionController::class, 'convertText'])
    ->middleware('throttle.conversions')
    ->name('convert.text');

Route::post('/convert/docx', [ConversionController::class, 'convertDocx'])
    ->middleware('throttle.conversions')
    ->name('convert.docx');

Route::post('/convert/xlsx', [ConversionController::class, 'convertXlsx'])
    ->middleware('throttle.conversions')
    ->name('convert.xlsx');

Route::post('/xlsx/headers', [ConversionController::class, 'getXlsxHeaders'])
    ->middleware('throttle.conversions')
    ->name('xlsx.headers');

// Debug route - SECURITY: Only accessible in local/development environment
Route::get('/debug', function() {
    // SECURITY: Block access in production
    if (!app()->environment(['local', 'development', 'testing'])) {
        abort(403, 'Debug endpoint is disabled in production');
    }

    return response()->json([
        'environment' => app()->environment(),
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
        'php_max_execution_time' => ini_get('max_execution_time'),
        'php_max_input_time' => ini_get('max_input_time'),
    ]);
})->name('debug');
