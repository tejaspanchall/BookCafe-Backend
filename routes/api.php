<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookImportController;

Route::prefix('books')->group(function () {
    Route::get('/search', [BookController::class, 'search']);
    Route::get('/get-books', [BookController::class, 'getBooks']);
    Route::get('/book/{id}', [BookController::class, 'getBook']);
    Route::get('/popular', [BookController::class, 'getPopularBooks']);
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}/books', [CategoryController::class, 'getBooks']);
});

Route::prefix('authors')->group(function () {
    Route::get('/', [AuthorController::class, 'index']);
    Route::get('/{id}/books', [AuthorController::class, 'getBooks']);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('books')->group(function () {
        // Routes for all authenticated users
        Route::get('/my-library', [BookController::class, 'myLibrary']);
        Route::post('/{book}/add-to-library', [BookController::class, 'addToLibrary']);
        Route::delete('/{book}/remove-from-library', [BookController::class, 'removeFromLibrary']);
        Route::get('/get-library', [BookController::class, 'getLibrary']);
        
        // Routes that require teacher role
        Route::middleware('teacher')->group(function () {
            Route::post('/add', [BookController::class, 'add']);
            Route::post('/add-multiple', [BookController::class, 'addMultiple']);
            Route::get('/export', [BookController::class, 'exportBooks']);
            Route::put('/{book}', [BookController::class, 'update']);
            Route::delete('/{book}', [BookController::class, 'deleteBook']);
        });
    });
    
    // Excel import routes (teachers only)
    Route::middleware('teacher')->prefix('excel-imports')->group(function () {
        Route::post('/upload', [BookImportController::class, 'uploadExcel']);
        Route::get('/files', [BookImportController::class, 'getExcelFiles']);
        Route::delete('/file/{fileId}', [BookImportController::class, 'deleteExcelFile']);
        Route::post('/import/{fileId}', [BookImportController::class, 'importBooks']);
        Route::get('/template', [BookImportController::class, 'downloadTemplate']);
    });
    
    // Category management (teachers only)
    Route::middleware('teacher')->prefix('categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
    });
});