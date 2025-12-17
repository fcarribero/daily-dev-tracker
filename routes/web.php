<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntryController;

Route::get('/', [EntryController::class, 'index'])->name('entries.index');
Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');
Route::post('/daily-summary', [EntryController::class, 'summary'])->name('entries.summary');
Route::put('/entries/{entry}', [EntryController::class, 'update'])->name('entries.update');
Route::delete('/entries/{entry}', [EntryController::class, 'destroy'])->name('entries.destroy');
