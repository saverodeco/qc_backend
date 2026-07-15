<?php

use App\Http\Controllers\Api\QcController;
use Illuminate\Support\Facades\Route;

/**
 * Tempel isi file ini ke routes/api.php yang sudah ada,
 * atau require langsung dari sana.
 */
Route::prefix('qc')->group(function () {
    Route::get('/', [QcController::class, 'index']);
    Route::get('lookup/{no_iml}', [QcController::class, 'lookup']);
    Route::post('{no_iml}/submit', [QcController::class, 'submit']);
    Route::post('{no_iml}/photos', [QcController::class, 'uploadPhoto']);
    Route::patch('{no_iml}/photos/{id}', [QcController::class, 'updatePhotoMc']);
    Route::delete('{no_iml}/photos/{id}', [QcController::class, 'deletePhoto']);
});