<?php

namespace Init\Commerce\Cart\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Init\Commerce\Cart\Models\CartItem;
use Init\Commerce\Cart\Support\CatalogItemResolver;
use Init\VisitorSession\Support\ResolvedActor;

class AddCatalogItemToCart
{
    public function __construct(
        private readonly ResolveActiveCart $resolveActiveCart,
        private readonly CatalogItemResolver $catalogItemResolver,
    ) {}

    public function execute(
        ResolvedActor $actor,
        Model|string $catalogItem,
        int $quantity = 1,
        bool $replace = false,
        ?string $catalogItemType = null,
    ): CartItem {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ['Quantity must be greater than zero.'],
            ]);
        }

        $cart = $this->resolveActiveCart->execute($actor, createIfMissing: true);
        $catalogRecord = $this->catalogItemResolver->resolve($catalogItem, $catalogItemType);
        $payload = $this->catalogItemResolver->makeCartLinePayload($catalogRecord);

        return DB::transaction(function () use ($cart, $payload, $quantity, $replace): CartItem {
            $lineCurrency = data_get($payload, 'pricing_snapshot.currency');
            $cartHasItems = $cart->items()->exists();

            if (! is_string($lineCurrency) || blank($lineCurrency)) {
                throw ValidationException::withMessages([
                    'currency' => ['Catalog item currency is required.'],
                ]);
            }

            if (! $cartHasItems) {
                $cart->forceFill([
                    'currency' => $lineCurrency,
                ])->saveQuietly();
            } elseif ($cart->currency !== $lineCurrency) {
                throw ValidationException::withMessages([
                    'currency' => ['All cart items must use the same currency.'],
                ]);
            }

            $item = $cart->items()
                ->where('catalog_item_type', $payload['catalog_item_type'])
                ->where('catalog_item_id', $payload['catalog_item_id'])
                ->lockForUpdate()
                ->first();

            if ($item instanceof CartItem) {
                $item->fill($payload);
                $item->quantity = $replace ? $quantity : ($item->quantity + $quantity);
                $item->save();
            } else {
                /** @var CartItem $item */
                $item = $cart->items()->create([
                    ...$payload,
                    'quantity' => $quantity,
                ]);
            }

            $cart->forceFill([
                'last_activity_at' => now(),
            ])->saveQuietly();
            $cart->refreshAggregates();

            return $item->fresh();
        });
    }
}
