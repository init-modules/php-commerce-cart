<?php

use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Init\Commerce\Catalog\Enums\CatalogInventoryMode;
use Init\Commerce\Catalog\Enums\CatalogItemStatus;
use Init\Commerce\Catalog\Enums\CatalogItemType;
use Init\Commerce\Catalog\Models\CatalogItem;
use Init\VisitorSession\Models\VisitorSession;

function visitorSessionHeaders(?string $sessionId = null): array
{
    return [
        'X-Visitor-Session' => $sessionId ?? (string) Str::uuid(),
    ];
}

function createCatalogOffer(array $attributes = []): CatalogItem
{
    return CatalogItem::query()->create([
        'type' => CatalogItemType::PRODUCT,
        'status' => CatalogItemStatus::ACTIVE,
        'sku' => 'ITEM-'.Str::upper(Str::random(8)),
        'name' => 'Commerce Item',
        'slug' => 'commerce-item-'.Str::lower(Str::random(8)),
        'base_price_amount' => 1000,
        'currency' => 'KZT',
        'inventory_mode' => CatalogInventoryMode::TRACKED,
        ...$attributes,
    ]);
}

it('registers current cart routes', function () {
    expect(route('commerce.cart.api.current.show', [], false))->toBe('/api/commerce/cart/v1/current');
    expect(route('commerce.cart.api.current.sync', [], false))->toBe('/api/commerce/cart/v1/current/sync');
    expect(route('commerce.cart.api.current.items.store', [], false))->toBe('/api/commerce/cart/v1/current/items');
});

it('syncs visitor cart lines into the authenticated actor cart', function () {
    $headers = visitorSessionHeaders();
    $product = createCatalogOffer([
        'sku' => 'SYNC-ITEM-001',
        'name' => 'Sync Item',
        'slug' => 'sync-item',
        'inventory_mode' => CatalogInventoryMode::UNTRACKED,
    ]);

    $this->withHeaders($headers)
        ->postJson('/api/commerce/cart/v1/current/items', [
            'catalog_item_id' => (string) $product->getKey(),
            'quantity' => 2,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.actor.authenticated', false);

    $user = new class extends AuthenticatableUser {
        public $incrementing = false;

        protected $keyType = 'string';

        public function getAuthIdentifierName()
        {
            return 'id';
        }
    };
    $user->forceFill(['id' => 'cart-sync-user']);
    $this->actingAs($user);

    $this->withHeaders($headers)
        ->postJson('/api/commerce/cart/v1/current/sync')
        ->assertSuccessful()
        ->assertJsonPath('data.actor.authenticated', true)
        ->assertJsonPath('data.items_quantity', 2)
        ->assertJsonPath('data.items.0.name', 'Sync Item');
});

it('rejects catalog item types outside the commerce whitelist', function () {
    $this->withHeaders(visitorSessionHeaders())
        ->postJson('/api/commerce/cart/v1/current/items', [
            'catalog_item_id' => (string) Str::uuid(),
            'catalog_item_type' => VisitorSession::class,
            'quantity' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['catalog_item_type']);
});

it('rejects mixed-currency items in the same cart', function () {
    $headers = visitorSessionHeaders();

    $kztItem = createCatalogOffer([
        'sku' => 'KZT-ITEM-001',
        'name' => 'KZT Item',
        'slug' => 'kzt-item',
        'currency' => 'KZT',
    ]);

    $usdItem = createCatalogOffer([
        'sku' => 'USD-ITEM-001',
        'name' => 'USD Item',
        'slug' => 'usd-item',
        'currency' => 'USD',
        'manual_price_amount' => 25,
    ]);

    $this->withHeaders($headers)
        ->postJson('/api/commerce/cart/v1/current/items', [
            'catalog_item_id' => (string) $kztItem->getKey(),
            'quantity' => 1,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.currency', 'KZT');

    $this->withHeaders($headers)
        ->postJson('/api/commerce/cart/v1/current/items', [
            'catalog_item_id' => (string) $usdItem->getKey(),
            'quantity' => 1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['currency']);
});
