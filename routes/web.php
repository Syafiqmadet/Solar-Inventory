<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\FuelController;
use App\Http\Controllers\FuelExportController;
use App\Http\Controllers\IsolatedItemController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubconController;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});
Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Project selection (no project required yet)
    Route::get('projects/select',  [ProjectController::class, 'select'])->name('projects.select');
    Route::post('projects/choose', [ProjectController::class, 'choose'])->name('projects.choose');
    Route::get('projects/switch',  [ProjectController::class, 'switchProject'])->name('projects.switch');

    // All routes below require a project to be selected
    Route::middleware('project')->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Admin project management
        Route::middleware('writer')->group(function () {
            Route::get('projects',                  [ProjectController::class, 'index'])->name('projects.index');
            Route::get('projects/create',           [ProjectController::class, 'create'])->name('projects.create');
            Route::post('projects',                 [ProjectController::class, 'store'])->name('projects.store');
            Route::get('projects/{project}/edit',   [ProjectController::class, 'edit'])->name('projects.edit');
            Route::put('projects/{project}',        [ProjectController::class, 'update'])->name('projects.update');
            Route::delete('projects/{project}',     [ProjectController::class, 'destroy'])->name('projects.destroy');
            Route::patch('projects/{project}/toggle',  [ProjectController::class, 'toggle'])->name('projects.toggle');
        });

        // ITEMS
        Route::get('items',              [ItemController::class, 'index'])->name('items.index');
        Route::get('items/export/excel', [ItemController::class, 'export'])->name('items.export');
        Route::middleware('writer')->group(function () {
            Route::get('items/create',      [ItemController::class, 'create'])->name('items.create');
            Route::post('items',            [ItemController::class, 'store'])->name('items.store');
        });
        Route::get('items/{item}',          [ItemController::class, 'show'])->name('items.show');
        Route::middleware('writer')->group(function () {
            Route::get('items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
            Route::put('items/{item}',      [ItemController::class, 'update'])->name('items.update');
            Route::delete('items/{item}',   [ItemController::class, 'destroy'])->name('items.destroy');
        });

        // ISOLATED ITEMS
        Route::get('isolated', [IsolatedItemController::class, 'index'])->name('isolated.index');
        Route::middleware('writer')->group(function () {
            Route::get('isolated/create',  [IsolatedItemController::class, 'create'])->name('isolated.create');
            Route::post('isolated',        [IsolatedItemController::class, 'store'])->name('isolated.store');
        });
        Route::get('isolated/{isolated}',   [IsolatedItemController::class, 'show'])->name('isolated.show');
        Route::middleware('writer')->group(function () {
            Route::get('isolated/{isolated}/edit',  [IsolatedItemController::class, 'edit'])->name('isolated.edit');
            Route::put('isolated/{isolated}',       [IsolatedItemController::class, 'update'])->name('isolated.update');
            Route::delete('isolated/{isolated}',    [IsolatedItemController::class, 'destroy'])->name('isolated.destroy');
        });

        // ZONES
        Route::get('zones',            [ZoneController::class, 'index'])->name('zones.index');
        Route::get('zones/export/all', [ZoneController::class, 'exportAll'])->name('zones.export.all');
        Route::middleware('writer')->group(function () {
            Route::get('zones/create',  [ZoneController::class, 'create'])->name('zones.create');
            Route::post('zones',        [ZoneController::class, 'store'])->name('zones.store');
        });
        Route::get('zones/{zone}',        [ZoneController::class, 'show'])->name('zones.show');
        Route::get('zones/{zone}/export', [ZoneController::class, 'export'])->name('zones.export');
        Route::middleware('writer')->group(function () {
            Route::get('zones/{zone}/edit',   [ZoneController::class, 'edit'])->name('zones.edit');
            Route::put('zones/{zone}',        [ZoneController::class, 'update'])->name('zones.update');
            Route::delete('zones/{zone}',     [ZoneController::class, 'destroy'])->name('zones.destroy');
            Route::get('zones/{zone}/stock',  [ZoneController::class, 'stockForm'])->name('zones.stock.form');
            Route::post('zones/{zone}/stock', [ZoneController::class, 'stockTransaction'])->name('zones.stock');
        });

        // CONTAINERS
        Route::get('containers',              [ContainerController::class, 'index'])->name('containers.index');
        Route::get('containers/export/excel', [ContainerController::class, 'export'])->name('containers.export');
        Route::middleware('writer')->group(function () {
            Route::get('containers/create',  [ContainerController::class, 'create'])->name('containers.create');
            Route::post('containers',        [ContainerController::class, 'store'])->name('containers.store');
        });
        Route::get('containers/{container}', [ContainerController::class, 'show'])->name('containers.show');
        Route::middleware('writer')->group(function () {
            Route::get('containers/{container}/edit',  [ContainerController::class, 'edit'])->name('containers.edit');
            Route::put('containers/{container}',       [ContainerController::class, 'update'])->name('containers.update');
            Route::delete('containers/{container}',    [ContainerController::class, 'destroy'])->name('containers.destroy');
        });

        // FUEL
        Route::get('fuel',              [FuelController::class, 'index'])->name('fuel.index');
        Route::get('fuel/report/excel', [FuelExportController::class, 'export'])->name('fuel.export');
        Route::middleware('writer')->group(function () {
            Route::get('fuel/create',  [FuelController::class, 'create'])->name('fuel.create');
            Route::post('fuel',        [FuelController::class, 'store'])->name('fuel.store');
        });
        Route::get('fuel/{fuel}', [FuelController::class, 'show'])->name('fuel.show');
        Route::middleware('writer')->group(function () {
            Route::get('fuel/{fuel}/edit',      [FuelController::class, 'edit'])->name('fuel.edit');
            Route::put('fuel/{fuel}',           [FuelController::class, 'update'])->name('fuel.update');
            Route::delete('fuel/{fuel}',        [FuelController::class, 'destroy'])->name('fuel.destroy');
            Route::delete('fuel/{fuel}/image',  [FuelController::class, 'deleteImage'])->name('fuel.image.delete');
        });

        // VEHICLES
        Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::middleware('writer')->group(function () {
            Route::get('vehicles/create',  [VehicleController::class, 'create'])->name('vehicles.create');
            Route::post('vehicles',        [VehicleController::class, 'store'])->name('vehicles.store');
        });
        Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
        Route::middleware('writer')->group(function () {
            Route::get('vehicles/{vehicle}/edit',             [VehicleController::class, 'edit'])->name('vehicles.edit');
            Route::put('vehicles/{vehicle}',                  [VehicleController::class, 'update'])->name('vehicles.update');
            Route::delete('vehicles/{vehicle}',               [VehicleController::class, 'destroy'])->name('vehicles.destroy');
            Route::get('vehicles/{vehicle}/usage/create',     [VehicleController::class, 'usageForm'])->name('vehicles.usage.form');
            Route::post('vehicles/{vehicle}/usage',           [VehicleController::class, 'usageStore'])->name('vehicles.usage.store');
            Route::delete('vehicles/{vehicle}/usage/{usage}', [VehicleController::class, 'usageDestroy'])->name('vehicles.usage.destroy');
        });


        // SUBCON
        Route::get('subcon', [SubconController::class, 'index'])->name('subcon.index');
        Route::middleware('writer')->group(function () {
            Route::post('subcon',            [SubconController::class, 'store'])->name('subcon.store');
            Route::put('subcon/{subcon}',    [SubconController::class, 'update'])->name('subcon.update');
            Route::delete('subcon/{subcon}', [SubconController::class, 'destroy'])->name('subcon.destroy');
        });
        // Static export routes BEFORE wildcard {mif}/{mrf} routes
        Route::get('subcon/check-number',            [SubconController::class, 'checkNumber'])->name('subcon.check.number');
        Route::get('subcon/{subcon}/mif/export', [SubconController::class, 'mifExport'])->name('subcon.mif.export');
        Route::get('subcon/{subcon}/mrf/export', [SubconController::class, 'mrfExport'])->name('subcon.mrf.export');

        Route::get('subcon/{subcon}/mif', [SubconController::class, 'mifIndex'])->name('subcon.mif');
        Route::middleware('writer')->group(function () {
            Route::post('subcon/{subcon}/mif',         [SubconController::class, 'mifStore'])->name('subcon.mif.store');
            Route::delete('subcon/{subcon}/mif/{mif}', [SubconController::class, 'mifDestroy'])->name('subcon.mif.destroy');
        });
        Route::get('subcon/{subcon}/mrf', [SubconController::class, 'mrfIndex'])->name('subcon.mrf');
        Route::middleware('writer')->group(function () {
            Route::post('subcon/{subcon}/mrf',         [SubconController::class, 'mrfStore'])->name('subcon.mrf.store');
            Route::delete('subcon/{subcon}/mrf/{mrf}', [SubconController::class, 'mrfDestroy'])->name('subcon.mrf.destroy');
        });

        // Admin users
        Route::resource('users', UserController::class)->except(['show']);

    }); // end project middleware

});
