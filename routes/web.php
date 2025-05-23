<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\SearchController;
use App\Models\Record;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');


/* Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard'); */

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
Route::get('/records/print',[RecordController::class, 'print'])->name('records.print');
Route::get('/records/selling',[RecordController::class, 'selling'])->name('records.selling');
Route::get('/artists/{artist}/print',[ArtistController::class, 'print'])->name('artists.print');
Route::get('/labels/{label}/print',[LabelController::class, 'print'])->name('labels.print');

Route::resource('dashboard', DashboardController::class);
Route::get('/records/api/',[SearchController::class, 'getAutocomplete'])->name('autocomplete');
Route::resource('artists', ArtistController::class);
Route::resource('records', RecordController::class);
Route::resource('platforms', PlatformController::class);
Route::resource('login', ProfileController::class);
//Route::resource('logout', LogoutController::class)->middleware('auth');
Route::resource('labels', LabelController::class);
//Route::resource('images', ImageController::class);


Route::get('/record/{id}/delete',[RecordController::class, 'destroy'])->name('record.destroy');
Route::get('/labels/{label}/print',[LabelController::class, 'print'])->name('labels.print');
Route::get('/artists/{artist}/print',[ArtistController::class, 'print'])->name('artists.print');
