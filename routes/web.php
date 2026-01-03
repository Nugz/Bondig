<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/upload', [UploadController::class, 'index'])->name('upload');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
Route::get('/products', [ProductController::class, 'index'])->name('products');
Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
