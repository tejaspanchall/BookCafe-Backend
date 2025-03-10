<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BookController;

Route::prefix('books')->group(function () {
    Route::get('/search', [BookController::class, 'search']);
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

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('books')->group(function () {
        Route::get('/get-books', [BookController::class, 'getBooks']);
        Route::get('/my-library', [BookController::class, 'myLibrary']);
        Route::post('/{book}/add-to-library', [BookController::class, 'addToLibrary']);
        Route::delete('/{book}/remove-from-library', [BookController::class, 'removeFromLibrary']);

        Route::get('/get-library', [BookController::class, 'getLibrary']);
        Route::post('/add', [BookController::class, 'add']);
        Route::put('/{book}', [BookController::class, 'updateBook']);
        Route::delete('/{book}', [BookController::class, 'deleteBook']);
    });
});