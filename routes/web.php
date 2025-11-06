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
