<?php

namespace Init\Commerce\Cart\Actions;

use Init\Commerce\Cart\Models\Cart;
use Init\Commerce\Cart\Models\CartItem;

class UpdateCartItemQuantity
{
    public function execute(CartItem $item, int $quantity): ?CartItem
    {
        if ($quantity <= 0) {
            $cart = $item->cart;
            $item->delete();
            $cart?->refreshAggregates();

            return null;
        }

        $item->forceFill([
            'quantity' => $quantity,
        ])->save();

        /** @var Cart|null $cart */
        $cart = $item->cart;
        $cart?->refreshAggregates();

        return $item->fresh();
    }
}
