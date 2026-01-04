<?php

use App\Http\Controllers\BonusMatchingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/upload', [UploadController::class, 'index'])->name('upload');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
Route::get('/products', [ProductController::class, 'index'])->name('products');
Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
Route::get('/receipts/{receipt}/match-bonuses', [BonusMatchingController::class, 'index'])->name('receipts.match-bonuses');
Route::post('/receipts/{receipt}/match-bonus/{bonus}', [BonusMatchingController::class, 'match'])->name('receipts.match-bonus');
