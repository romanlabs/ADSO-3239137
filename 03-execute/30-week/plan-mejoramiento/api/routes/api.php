<?php

use App\Infrastructure\Http\Controllers\AuthController;
use App\Infrastructure\Http\Controllers\CategoryController;
use App\Infrastructure\Http\Controllers\HealthController;
use App\Infrastructure\Http\Controllers\LaptopController;
use App\Infrastructure\Http\Controllers\LaptopPhotoController;
use App\Infrastructure\Http\Controllers\LoanController;
use App\Infrastructure\Http\Controllers\ReportController;
use App\Infrastructure\Http\Controllers\StudentController;
use App\Infrastructure\Http\Controllers\UserController;
use App\Infrastructure\Http\Middleware\ParseMultipartPut;
use Illuminate\Support\Facades\Route;

/*
 * The 21 routes of docs/api-contract.md §3, all under /api/v1 (prefix set in bootstrap/app.php).
 * `auth.token` answers 401 without a valid token; `role:` answers 403 before the controller runs.
 */

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/health', HealthController::class);

Route::middleware('auth.token')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/laptops', [LaptopController::class, 'index']);
    Route::get('/laptops/{id}', [LaptopController::class, 'show']);
    Route::get('/laptops/{id}/photo', [LaptopPhotoController::class, 'show']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/users', [UserController::class, 'store']);

        Route::post('/laptops', [LaptopController::class, 'store']);
        Route::put('/laptops/{id}', [LaptopController::class, 'update']);
        Route::delete('/laptops/{id}', [LaptopController::class, 'destroy']);
        Route::put('/laptops/{id}/photo', [LaptopPhotoController::class, 'update'])->middleware(ParseMultipartPut::class);
        Route::delete('/laptops/{id}/photo', [LaptopPhotoController::class, 'destroy']);

        Route::get('/reports/most-requested', [ReportController::class, 'mostRequested']);
    });

    Route::middleware('role:admin,assistant')->group(function () {
        Route::get('/students', [StudentController::class, 'index']);
        Route::get('/students/{id}', [StudentController::class, 'show']);
        Route::post('/students', [StudentController::class, 'store']);

        Route::post('/loans', [LoanController::class, 'store']);
        Route::get('/loans', [LoanController::class, 'index']);
        Route::get('/loans/overdue', [LoanController::class, 'overdue']);
        Route::get('/loans/{id}', [LoanController::class, 'show']);
        Route::post('/loans/{loan_id}/items/{item_id}/return', [LoanController::class, 'returnItem']);
    });
});
