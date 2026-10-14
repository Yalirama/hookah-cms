<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TobaccoController;
use App\Http\Controllers\MixController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\WikiController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', fn() => redirect()->route('orders.index'))->name('dashboard');

    Route::get('/orders/guests/search', [OrderController::class, 'searchGuests'])->name('orders.guests.search');

    Route::get('/orders/{order}/sheet', [OrderController::class, 'sheet'])->name('orders.sheet');
    Route::get('/orders/{order}/card', [OrderController::class, 'cardPartial'])->name('orders.card');
    Route::patch('orders/{order}/guest-comment', [OrderController::class, 'updateGuestComment'])->name('orders.guest-comment');

    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])
        ->name('analytics.index')
        ->middleware('auth');

    Route::get('/analytics/inactive-guests', [App\Http\Controllers\AnalyticsController::class, 'inactiveGuestsPartial'])
        ->name('analytics.inactive-guests')
        ->middleware('auth');

    Route::get('/analytics/content', [App\Http\Controllers\AnalyticsController::class, 'contentPartial'])
        ->name('analytics.content')
        ->middleware('auth');

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/',        [OrderController::class, 'index'])->name('index');
        Route::get('/create',  [OrderController::class, 'create'])->name('create');
        Route::post('/',       [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/status',         [OrderController::class, 'changeStatus'])->name('status');
        Route::post('/{order}/rollback',       [OrderController::class, 'rollbackStatus'])->name('rollback');
        Route::post('/{order}/table',          [OrderController::class, 'changeTable'])->name('table');
        Route::post('/{order}/service',        [OrderController::class, 'logService'])->name('service');
        Route::post('/{order}/items',          [OrderController::class, 'addItem'])->name('items.add');
        Route::delete('/{order}/items/{item}', [OrderController::class, 'removeItem'])->name('items.remove');
        Route::get('/{order}/live-data',       [OrderController::class, 'liveData'])->name('live-data');
        Route::post('/{order}/attach-guest',   [OrderController::class, 'attachGuest'])->name('attach-guest');
        Route::delete('/{order}/detach-guest', [OrderController::class, 'detachGuest'])->name('detach-guest');
        Route::patch('/{order}/logs/{log}/master', [OrderController::class, 'updateLogMaster'])->name('logs.master');
    });

    Route::post('/push/subscribe',   [\App\Http\Controllers\PushController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [\App\Http\Controllers\PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::post('/shift/toggle', [\App\Http\Controllers\PushController::class, 'toggleShift'])->name('shift.toggle');

    // Tobaccos
    Route::prefix('tobaccos')->name('tobaccos.')->group(function () {
        Route::get('/',              [TobaccoController::class, 'index'])->name('index');
        Route::get('/edit-venue',    [TobaccoController::class, 'edit'])->name('edit');
        Route::get('/edit-venue/sheet', [TobaccoController::class, 'editSheet'])->name('edit-venue.sheet');
        Route::get('/edit-venue/brand/{brand}', [TobaccoController::class, 'editBrandRows'])->name('edit-venue.brand');
        Route::get('/edit-venue/search',        [TobaccoController::class, 'editSearch'])->name('edit-venue.search');
        Route::post('/update-venue', [TobaccoController::class, 'updateVenue'])->name('update-venue');
        Route::post('/update-venue-bulk', [TobaccoController::class, 'updateVenueBulk'])->name('update-venue-bulk');
        Route::get('/brands/{brand}', [TobaccoController::class, 'showBrand'])->name('brands.show');
        Route::get('/stats-partial', [TobaccoController::class, 'statsPartial'])->name('stats-partial');
        Route::get('/list-partial', [TobaccoController::class, 'listPartial'])->name('list-partial');
        Route::get('/deactivation-log/sheet', [TobaccoController::class, 'deactivationLogSheet'])->name('deactivation-log.sheet');
        Route::get('/deactivation-log/more', [TobaccoController::class, 'deactivationLogMore'])->name('deactivation-log.more');
        Route::get('/{tobacco}',     [TobaccoController::class, 'show'])->name('show');
        Route::get('/{tobacco}/sheet', [TobaccoController::class, 'sheet'])->name('sheet');
        Route::post('/{tobacco}/notes',          [TobaccoController::class, 'storeNote'])->name('notes.store');
        Route::delete('/{tobacco}/notes/{note}', [TobaccoController::class, 'destroyNote'])->name('notes.destroy');
        Route::post('/{tobacco}/deactivate', [TobaccoController::class, 'deactivate'])->name('deactivate');
        Route::post('/{tobacco}/activate',   [TobaccoController::class, 'activate'])->name('activate');
        
    });

    // Mixes
    Route::prefix('mixes')->name('mixes.')->group(function () {
        Route::get('/',                      [MixController::class, 'index'])->name('index');
        Route::get('/create',                [MixController::class, 'create'])->name('create');
        Route::post('/',                     [MixController::class, 'store'])->name('store');
        Route::get('/deactivation-log/sheet', [MixController::class, 'deactivationLogSheet'])->name('deactivation-log.sheet');
        Route::get('/deactivation-log/more', [MixController::class, 'deactivationLogMore'])->name('deactivation-log.more');
        Route::get('/stats-partial', [MixController::class, 'statsPartial'])->name('stats-partial');
        Route::get('/tobaccos-list', [MixController::class, 'tobaccosListJson'])->name('tobaccos-list');
        Route::get('/create-form', [MixController::class, 'createForm'])->name('create-form');
        Route::get('/{mix}/edit-form', [MixController::class, 'editForm'])->name('edit-form');
        Route::get('/list-partial', [MixController::class, 'listPartial'])->name('list-partial');
        Route::get('/{mix}',                 [MixController::class, 'show'])->name('show');
        Route::get('/{mix}/edit',            [MixController::class, 'edit'])->name('edit');
        Route::get('/{mix}/history/sheet', [MixController::class, 'historySheet'])->name('history.sheet');
        Route::put('/{mix}',                 [MixController::class, 'update'])->name('update');
        Route::get('/{mix}/sheet', [MixController::class, 'sheet'])->name('sheet');
        Route::post('/{mix}/activate',       [MixController::class, 'activate'])->name('activate');
        Route::post('/{mix}/deactivate',     [MixController::class, 'deactivate'])->name('deactivate');
        Route::post('/{mix}/notes',          [MixController::class, 'storeNote'])->name('notes.store');
        Route::delete('/{mix}/notes/{note}', [MixController::class, 'destroyNote'])->name('notes.destroy');
    });

    // Guests
    Route::prefix('guests')->name('guests.')->group(function () {
        Route::get('/',                      [GuestController::class, 'index'])->name('index');
        Route::get('/list-partial',          [GuestController::class, 'listPartial'])->name('list-partial');
        Route::get('/stats-partial',         [GuestController::class, 'statsPartial'])->name('stats-partial');
        Route::get('/create-form',           [GuestController::class, 'createForm'])->name('create-form');
        Route::post('/',                     [GuestController::class, 'store'])->name('store');
        Route::get('/labels-list',           [GuestController::class, 'labelsListJson'])->name('labels-list');
        Route::get('/{guest}',               [GuestController::class, 'show'])->name('show');
        Route::get('/{guest}/sheet',         [GuestController::class, 'sheet'])->name('sheet');
        Route::get('/{guest}/edit-form',     [GuestController::class, 'editForm'])->name('edit-form');
        Route::put('/{guest}',               [GuestController::class, 'update'])->name('update');
        Route::post('/{guest}/status',       [GuestController::class, 'setStatus'])->name('status');
        Route::post('/{guest}/notes',        [GuestController::class, 'storeNote'])->name('notes.store');
        Route::delete('/{guest}/notes/{note}', [GuestController::class, 'destroyNote'])->name('notes.destroy');
        Route::get('/{guest}/labels/sheet',  [GuestController::class, 'labelsSheet'])->name('labels.sheet');
        Route::get('/{guest}/history/sheet', [GuestController::class, 'historySheet'])->name('history.sheet');
        Route::post('/{guest}/labels',       [GuestController::class, 'addLabel'])->name('labels.add');
        Route::delete('/{guest}/labels/{guestLabel}', [GuestController::class, 'removeLabel'])->name('labels.remove');
        Route::get('/{guest}/visit-history/sheet', [GuestController::class, 'visitHistorySheet'])->name('visit-history.sheet');
        Route::get('/{guest}/visit-history/more',  [GuestController::class, 'visitHistoryMore'])->name('visit-history.more');
    });

    // Labels
    Route::prefix('labels')->name('labels.')->group(function () {
        Route::get('/', [LabelController::class, 'index'])->name('index');
        Route::post('/', [LabelController::class, 'store'])->name('store');
        Route::get('/{label}/sheet', [LabelController::class, 'sheet'])->name('sheet');
        Route::put('/{label}', [LabelController::class, 'update'])->name('update');
        Route::get('/{label}/history-sheet', [LabelController::class, 'historySheet'])->name('history-sheet');
        Route::post('/{label}/toggle', [LabelController::class, 'toggle'])->name('toggle');
        Route::post('/{label}/notes', [LabelController::class, 'storeNote'])->name('notes.store');
        Route::delete('/{label}/notes/{note}', [LabelController::class, 'destroyNote'])->name('notes.destroy');
    });

    Route::prefix('wiki')->name('wiki.')->group(function () {
        Route::get('/', [WikiController::class, 'index'])->name('index');

        // Admin — статичные роуты ДО /{wikiPage}
        Route::middleware('role:admin')->group(function () {
            Route::get('/create',        [WikiController::class, 'create'])->name('create');
            Route::post('/',             [WikiController::class, 'store'])->name('store');
            Route::post('/upload-image', [WikiController::class, 'uploadImage'])->name('upload-image');
            Route::post('/reorder',      [WikiController::class, 'reorder'])->name('reorder');
        });

        // Динамические роуты — ПОСЛЕ статичных
        Route::get('/{wikiPage}',      [WikiController::class, 'show'])->name('show');
        Route::get('/{wikiPage}/sheet', [WikiController::class, 'showSheet'])->name('show-sheet');
        Route::middleware('role:admin')->group(function () {
            Route::get('/{wikiPage}/edit', [WikiController::class, 'edit'])->name('edit');
            Route::put('/{wikiPage}',      [WikiController::class, 'update'])->name('update');
        });
    });

    // Admin only
    Route::middleware('role:admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('general');
        Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications');
        Route::post('/thresholds', [SettingsController::class, 'updateThresholds'])->name('thresholds');
        Route::post('/inactive', [SettingsController::class, 'updateInactive'])->name('inactive');

        Route::post('/masters', [SettingsController::class, 'storeMaster'])->name('masters.store');
        Route::put('/masters/{user}', [SettingsController::class, 'updateMaster'])->name('masters.update');
        Route::post('/masters/{user}/toggle', [SettingsController::class, 'toggleMaster'])->name('masters.toggle');

        Route::post('/table-types', [SettingsController::class, 'storeTableType'])->name('table-types.store');
        Route::put('/table-types/{tableType}', [SettingsController::class, 'updateTableType'])->name('table-types.update');
        Route::post('/table-types/{tableType}/toggle', [SettingsController::class, 'toggleTableType'])->name('table-types.toggle');

        Route::get('/tables-panel', [SettingsController::class, 'tablesPanel'])->name('tables-panel');
        Route::post('/tables', [SettingsController::class, 'storeTable'])->name('tables.store');
        Route::put('/tables/{table}', [SettingsController::class, 'updateTable'])->name('tables.update');
        Route::post('/tables/{table}/toggle', [SettingsController::class, 'toggleTable'])->name('tables.toggle');

        Route::get('/master-changes/sheet', [SettingsController::class, 'masterChangesSheet'])->name('master-changes.sheet');
        Route::get('/tobacco-deactivation-log', [TobaccoController::class, 'deactivationLog'])->name('tobaccos.deactivation-log');

        Route::get('/wiki/create-sheet', [WikiController::class, 'createSheet'])->name('wiki.create-sheet');
        Route::get('/wiki/{wikiPage}/edit-sheet', [WikiController::class, 'editSheet'])->name('wiki.edit-sheet');
        Route::get('/wiki/{wikiPage}/view-sheet', [WikiController::class, 'viewSheet'])->name('wiki.view-sheet');
        Route::post('/wiki', [WikiController::class, 'storeAjax'])->name('wiki.store-ajax');
        Route::put('/wiki/{wikiPage}', [WikiController::class, 'updateAjax'])->name('wiki.update-ajax');
        Route::post('/wiki/{wikiPage}/toggle', [WikiController::class, 'togglePublish'])->name('wiki.toggle');
    });
});