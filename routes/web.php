<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/upload', [UploadController::class, 'index'])->name('upload');
Route::get('/products', [ProductController::class, 'index'])->name('products');
