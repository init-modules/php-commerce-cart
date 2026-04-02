<?php

namespace Init\Commerce\Cart\Actions;

use Init\Commerce\Cart\Models\Cart;
use Init\Commerce\Cart\Enums\CartStatus;
use Init\VisitorSession\Support\ResolvedActor;

class ResolveActiveCart
{
    public function execute(
        ResolvedActor $actor,
        bool $createIfMissing = true,
        ?string $currency = null,
    ): ?Cart {
        /** @var class-string<Cart> $cartModel */
        $cartModel = config('commerce_cart.models.cart', Cart::class);
        $activeActorKey = Cart::makeActiveActorKey($actor);

        $cart = $cartModel::query()
            ->where('active_actor_key', $activeActorKey)
            ->first();

        if ($cart instanceof Cart) {
            $cart->forceFill([
                'last_activity_at' => now(),
            ])->saveQuietly();

            return $cart->loadMissing('items');
        }

        if (! $createIfMissing) {
            return null;
        }

        /** @var Cart $cart */
        $cart = $cartModel::query()->create([
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'actor_authenticated' => $actor->authenticated,
            'status' => CartStatus::ACTIVE,
            'currency' => $currency ?? config('commerce_cart.pricing.default_currency'),
            'active_actor_key' => $activeActorKey,
            'last_activity_at' => now(),
        ]);

        return $cart->load('items');
    }
}
