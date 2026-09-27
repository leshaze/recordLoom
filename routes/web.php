<?php

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscogsController;
use App\Http\Controllers\EditionController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\RecordImportController;
use App\Http\Controllers\SearchController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');

// Language switch in the navigation (German / English).
Route::get('/language/{locale}', function (string $locale) {
    session(['locale' => $locale]);

    return back();
})->whereIn('locale', SetLocale::LOCALES)->name('language');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/records/print', [RecordController::class, 'print'])->name('records.print');
Route::get('/records/selling', [RecordController::class, 'selling'])->name('records.selling');
Route::get('/records/export', [RecordController::class, 'export'])->name('records.export');
Route::get('/records/import', [RecordImportController::class, 'create'])->name('records.import');
Route::post('/records/import', [RecordImportController::class, 'store'])->middleware('throttle:10,1')->name('records.import.store');
Route::get('/records/{record}/cover', [RecordController::class, 'cover'])->name('records.cover');
Route::get('/records/api', [SearchController::class, 'getAutocomplete'])
    ->middleware('throttle:120,1')
    ->name('autocomplete');
Route::get('/artists/{artist}/print', [ArtistController::class, 'print'])->name('artists.print');
Route::get('/labels/{label}/print', [LabelController::class, 'print'])->name('labels.print');

// Discogs: search and release data for the record form, market data and matching of existing records.
Route::middleware('throttle:40,1')->prefix('discogs')->name('discogs.')->group(function () {
    Route::get('/search', [DiscogsController::class, 'search'])->name('search');
    Route::get('/releases/{release}', [DiscogsController::class, 'release'])->whereNumber('release')->name('release');
    Route::get('/records/{record}/suggestions', [DiscogsController::class, 'suggestions'])->name('suggestions');
    Route::post('/records/{record}/prices', [DiscogsController::class, 'updatePrices'])->name('prices');
    Route::post('/records/{record}/apply-price', [DiscogsController::class, 'applyPrice'])->name('apply-price');
    Route::post('/records/{record}/link', [DiscogsController::class, 'link'])->name('link');
    Route::post('/records/{record}/ignore', [DiscogsController::class, 'ignore'])->name('ignore');
    Route::delete('/records/{record}/link', [DiscogsController::class, 'unlink'])->name('unlink');
});
Route::get('/discogs/match', [DiscogsController::class, 'match'])->name('discogs.match');

Route::resource('editions', EditionController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('artists', ArtistController::class);
Route::resource('labels', LabelController::class);
Route::resource('platforms', PlatformController::class);
Route::resource('records', RecordController::class);
