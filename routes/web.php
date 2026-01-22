<?php

use GreeLogix\RequestLogger\Http\Controllers\LogViewerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->prefix('gl')->group(function () {
    Route::get('/request-logs', [LogViewerController::class, 'index'])->name('gl.request-logger.index');
    Route::get('/request-logs/{id}', [LogViewerController::class, 'show'])->name('gl.request-logger.show');
    Route::get('/request-logs-check-new', [LogViewerController::class, 'checkNew'])->name('gl.request-logger.check-new');
    Route::delete('/request-logs', [LogViewerController::class, 'destroy'])->name('gl.request-logger.destroy');
});
