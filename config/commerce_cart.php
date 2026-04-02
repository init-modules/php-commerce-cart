<?php

return [
    'models' => [
        'cart' => Init\Commerce\Cart\Models\Cart::class,
        'cart_item' => Init\Commerce\Cart\Models\CartItem::class,
        'catalog_item' => Init\Commerce\Catalog\Models\CatalogItem::class,
    ],

    'api' => [
        'enabled' => true,
        'prefix' => 'commerce/cart/v1',
        'name_prefix' => 'commerce.cart.api.',
        'middleware' => ['api'],
    ],

    'filament' => [
        'enabled' => true,
        'panel' => 'admin',
    ],

    'pricing' => [
        'scale' => 2,
        'default_currency' => 'KZT',
    ],

    'catalog_item' => [
        'allowed_models' => [
            Init\Commerce\Catalog\Models\CatalogItem::class,
        ],
        'attributes' => [
            'name' => ['name', 'title', 'label'],
            'sku' => ['sku', 'code'],
            'type' => ['item_type', 'type', 'kind'],
            'base_price' => ['base_price', 'cost_price', 'price'],
            'unit_price' => ['public_price', 'price', 'effective_price'],
            'currency' => ['currency', 'currency_code'],
            'tracked' => ['tracked', 'track_stock', 'is_tracked'],
        ],
    ],
];
