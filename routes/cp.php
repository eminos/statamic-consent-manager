<?php

use Illuminate\Support\Facades\Route;
use Eminos\StatamicConsentManager\Http\Controllers\ConsentManagerController;

Route::prefix('consent-manager')->name('consent-manager.')->group(function () {
    Route::get('/', [ConsentManagerController::class, 'edit'])->name('edit');
    Route::patch('/', [ConsentManagerController::class, 'update'])->name('update');
});
