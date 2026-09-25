<?php

use App\Http\Controllers\ArtistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// The whole archive is only accessible for logged in users.
// There is no public registration, users are created with `php artisan user:create`.
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Only admins may create, change and delete entries.
    // These routes are registered first, so "create" is not taken for a {record} id.
    Route::middleware('can:admin')->group(function () {
        Route::resource('artists', ArtistController::class)->except(['index', 'show']);
        Route::resource('labels', LabelController::class)->except(['index', 'show']);
        Route::resource('platforms', PlatformController::class)->except(['index', 'show']);
        Route::resource('records', RecordController::class)->except(['index', 'show']);

        Route::get('/admin/interests', [InterestController::class, 'overview'])->name('interests.overview');
    });

    // Interests of the logged in (non admin) user.
    Route::middleware('can:mark-interest')->group(function () {
        Route::get('/interests', [InterestController::class, 'index'])->name('interests.index');
        Route::put('/records/{record}/interest', [InterestController::class, 'update'])->name('interests.update');
    });

    // Read access for all users.
    Route::get('/records/print', [RecordController::class, 'print'])->name('records.print');
    Route::get('/records/selling', [RecordController::class, 'selling'])->name('records.selling');
    Route::get('/records/api', [SearchController::class, 'getAutocomplete'])
        ->middleware('throttle:120,1')
        ->name('autocomplete');
    Route::get('/artists/{artist}/print', [ArtistController::class, 'print'])->name('artists.print');
    Route::get('/labels/{label}/print', [LabelController::class, 'print'])->name('labels.print');

    Route::resource('artists', ArtistController::class)->only(['index', 'show']);
    Route::resource('labels', LabelController::class)->only(['index', 'show']);
    Route::resource('platforms', PlatformController::class)->only(['index', 'show']);
    Route::resource('records', RecordController::class)->only(['index', 'show']);
});
