<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscogsController;
use App\Http\Controllers\EditionController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\RecordImportController;
use App\Http\Controllers\RecordSaleController;
use App\Http\Controllers\SearchController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

// Language switch in the navigation (German / English), also on the login page.
Route::get('/language/{locale}', function (string $locale) {
    session(['locale' => $locale]);

    return back();
})->whereIn('locale', SetLocale::LOCALES)->name('language');

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');

    // Everyone (sellers: only the records assigned to them, checked by RecordPolicy).
    Route::get('/records/barcode', [RecordController::class, 'barcode'])->name('records.barcode');
    Route::get('/records/{record}/cover', [RecordController::class, 'cover'])->name('records.cover');
    Route::post('/records/{record}/interest', [RecordSaleController::class, 'interest'])->name('records.interest');

    // Admins and users: their collection.
    Route::middleware('can:manage-collection')->group(function () {
        Route::get('/records/print', [RecordController::class, 'print'])->name('records.print');
        Route::get('/records/duplicates', [RecordController::class, 'duplicates'])->middleware('throttle:120,1')->name('records.duplicates');
        Route::get('/records/export', [RecordController::class, 'export'])->name('records.export');
        Route::get('/records/import', [RecordImportController::class, 'create'])->name('records.import');
        Route::post('/records/import', [RecordImportController::class, 'store'])->middleware('throttle:10,1')->name('records.import.store');
        Route::get('/records/api', [SearchController::class, 'getAutocomplete'])
            ->middleware('throttle:120,1')
            ->name('autocomplete');
        Route::put('/records/{record}/sellers', [RecordSaleController::class, 'sellers'])->name('records.sellers');
        Route::post('/records/{record}/sell', [RecordSaleController::class, 'sell'])->name('records.sell');
        Route::put('/records/{record}/selling', [RecordSaleController::class, 'selling'])->name('records.selling');
        Route::resource('records', RecordController::class)->except(['index', 'show']);

        Route::get('/artists/{artist}/print', [ArtistController::class, 'print'])->name('artists.print');
        Route::get('/labels/{label}/print', [LabelController::class, 'print'])->name('labels.print');
        Route::resource('artists', ArtistController::class);
        Route::resource('labels', LabelController::class);
        Route::resource('platforms', PlatformController::class);

        // Discogs: search and release data for the record form, market data and matching of existing records.
        Route::middleware('throttle:40,1')->prefix('discogs')->name('discogs.')->group(function () {
            Route::get('/search', [DiscogsController::class, 'search'])->name('search');
            Route::get('/releases/{release}', [DiscogsController::class, 'release'])->whereNumber('release')->name('release');
            Route::get('/records/{record}/suggestions', [DiscogsController::class, 'suggestions'])->name('suggestions');
            Route::post('/records/{record}/prices', [DiscogsController::class, 'updatePrices'])->name('prices');
            Route::post('/records/{record}/apply-price', [DiscogsController::class, 'applyPrice'])->name('apply-price');
            Route::get('/records/{record}/review', [DiscogsController::class, 'review'])->name('review');
            Route::post('/records/{record}/link', [DiscogsController::class, 'link'])->name('link');
            Route::post('/records/{record}/ignore', [DiscogsController::class, 'ignore'])->name('ignore');
            Route::delete('/records/{record}/link', [DiscogsController::class, 'unlink'])->name('unlink');
        });
        Route::get('/discogs/match', [DiscogsController::class, 'match'])->name('discogs.match');
    });

    // After the fixed paths like /records/create, which would otherwise be taken for a record.
    Route::resource('records', RecordController::class)->only(['index', 'show']);

    // Admins: users and the shared list of "Zusatzinfos".
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/users/{user}/invite', [UserController::class, 'invite'])->name('users.invite');
        Route::resource('users', UserController::class)->except(['show']);
    });
    Route::resource('editions', EditionController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('can:admin');
});
