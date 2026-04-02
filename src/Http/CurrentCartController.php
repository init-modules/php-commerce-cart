<?php

namespace Init\Commerce\Cart\Http;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Init\Commerce\Cart\Actions\AddCatalogItemToCart;
use Init\Commerce\Cart\Actions\RemoveCartItem;
use Init\Commerce\Cart\Actions\ResolveActiveCart;
use Init\Commerce\Cart\Actions\UpdateCartItemQuantity;
use Init\Commerce\Cart\Models\Cart;
use Init\Commerce\Cart\Models\CartItem;
use Init\VisitorSession\Support\RequestActorResolver;
use Init\VisitorSession\Support\ResolvedActor;

class CurrentCartController
{
    public function __construct(
        private readonly RequestActorResolver $requestActorResolver,
        private readonly ResolveActiveCart $resolveActiveCart,
        private readonly AddCatalogItemToCart $addCatalogItemToCart,
        private readonly UpdateCartItemQuantity $updateCartItemQuantity,
        private readonly RemoveCartItem $removeCartItem,
    ) {}

    public function show(Request $request): array
    {
        $actor = $this->resolveActor($request);
        $cart = $this->resolveActiveCart->execute($actor, createIfMissing: true);

        return [
            'data' => $this->transformCart($cart->load('items')),
        ];
    }

    public function storeItem(Request $request): array
    {
        $actor = $this->resolveActor($request);
        $payload = $request->validate([
            'catalog_item_id' => ['required', 'string'],
            'catalog_item_type' => ['nullable', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'replace' => ['nullable', 'boolean'],
        ]);

        $item = $this->addCatalogItemToCart->execute(
            actor: $actor,
            catalogItem: $payload['catalog_item_id'],
            quantity: (int) ($payload['quantity'] ?? 1),
            replace: (bool) ($payload['replace'] ?? false),
            catalogItemType: $payload['catalog_item_type'] ?? null,
        );

        return [
            'data' => $this->transformCart($item->cart->fresh('items')),
        ];
    }

    public function updateItem(Request $request, string $item): array
    {
        $actor = $this->resolveActor($request);
        $cart = $this->resolveOwnedCart($actor);
        $payload = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cartItem = $cart->items()->findOrFail($item);
        $updatedItem = $this->updateCartItemQuantity->execute($cartItem, (int) $payload['quantity']);

        return [
            'data' => $this->transformCart(
                $updatedItem?->cart?->fresh('items') ?? $cart->fresh('items')
            ),
        ];
    }

    public function destroyItem(Request $request, string $item): array
    {
        $actor = $this->resolveActor($request);
        $cart = $this->resolveOwnedCart($actor);

        $cartItem = $cart->items()->findOrFail($item);
        $this->removeCartItem->execute($cartItem);

        return [
            'data' => $this->transformCart($cart->fresh('items')),
        ];
    }

    private function resolveActor(Request $request): ResolvedActor
    {
        $actor = $this->requestActorResolver->resolve($request);

        if ($actor instanceof ResolvedActor) {
            return $actor;
        }

        throw ValidationException::withMessages([
            'actor' => ['Authenticated user or X-Visitor-Session header is required.'],
        ]);
    }

    private function resolveOwnedCart(ResolvedActor $actor): Cart
    {
        $cart = $this->resolveActiveCart->execute($actor, createIfMissing: false);

        if (! $cart instanceof Cart) {
            throw ValidationException::withMessages([
                'cart' => ['Active cart was not found for the current actor.'],
            ]);
        }

        return $cart->load('items');
    }

    private function transformCart(Cart $cart): array
    {
        return [
            'id' => $cart->id,
            'status' => $cart->status?->value,
            'actor' => [
                'type' => $cart->actor_type,
                'id' => $cart->actor_id,
                'authenticated' => $cart->actor_authenticated,
            ],
            'currency' => $cart->currency,
            'item_count' => $cart->item_count,
            'items_quantity' => $cart->items_quantity,
            'subtotal_amount' => $cart->subtotal_amount,
            'total_amount' => $cart->total_amount,
            'checked_out_at' => $cart->checked_out_at?->toIso8601String(),
            'updated_at' => $cart->updated_at?->toIso8601String(),
            'items' => $cart->items
                ->map(fn (CartItem $item): array => [
                    'id' => $item->id,
                    'catalog_item_type' => $item->catalog_item_type,
                    'catalog_item_id' => $item->catalog_item_id,
                    'name' => $item->item_name,
                    'sku' => $item->item_sku,
                    'type' => $item->item_type,
                    'quantity' => $item->quantity,
                    'base_price' => $item->base_price,
                    'unit_price' => $item->unit_price,
                    'line_base_total' => $item->line_base_total,
                    'line_total' => $item->line_total,
                    'pricing_snapshot' => $item->pricing_snapshot,
                    'catalog_snapshot' => $item->catalog_snapshot,
                ])
                ->values()
                ->all(),
        ];
    }
}
