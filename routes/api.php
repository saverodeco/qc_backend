<?php

use App\Http\Controllers\Api\QcController;
use Illuminate\Support\Facades\Route;

Route::prefix('qc')->group(function () {
    Route::get('/', [QcController::class, 'index']);
    Route::get('lookup/{no_iml}', [QcController::class, 'lookup']);
    Route::get('inspectors', [QcController::class, 'searchInspectors']);
    Route::post('{no_iml}/submit', [QcController::class, 'submit']);
    Route::post('{no_iml}/photos', [QcController::class, 'uploadPhoto']);
    Route::delete('{no_iml}/photos/{id}', [QcController::class, 'deletePhoto']);
});