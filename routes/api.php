<?php

use Illuminate\Support\Facades\Route;
use Init\Commerce\Cart\Http\CurrentCartController;

Route::prefix(config('commerce_cart.api.prefix', 'commerce/cart/v1'))->group(function (): void {
    Route::get('current', [CurrentCartController::class, 'show'])
        ->name('current.show');

    Route::post('current/items', [CurrentCartController::class, 'storeItem'])
        ->name('current.items.store');

    Route::patch('current/items/{item}', [CurrentCartController::class, 'updateItem'])
        ->name('current.items.update');

    Route::delete('current/items/{item}', [CurrentCartController::class, 'destroyItem'])
        ->name('current.items.destroy');
});
