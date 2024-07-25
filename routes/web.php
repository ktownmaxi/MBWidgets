<?php

use FluxErp\Http\Controllers\AssetController;
use FluxErp\Http\Controllers\LoginLinkController;
use FluxErp\Livewire\Order\SignaturePublicLink;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('web')
    ->group(function () {
        Route::middleware('signed')->group(function () {
            Route::get('/login-link', LoginLinkController::class)->name('login-link');
            Route::get('/signature-public/{uuid}', SignaturePublicLink::class)->name('signature.public');
        });

        Route::middleware('cache.headers:public;max_age=31536000;etag')->group(function () {
            Route::get('/manifest.json', [AssetController::class, 'manifest'])->name('manifest');
            Route::get('favicon.svg', [AssetController::class, 'favicon'])->name('favicon');
            Route::get('/flux-assets/{file}', [AssetController::class, 'asset'])
                ->where('file', '.+')
                ->name('flux-asset');
            Route::get('/pwa-service-worker', [AssetController::class, 'pwaServiceWorker'])
                ->name('pwa-service-worker');
            Route::get('/mail-pixel/{communication:uuid}', [AssetController::class, 'mailPixel'])
                ->name('mail-pixel');
        });
    });
