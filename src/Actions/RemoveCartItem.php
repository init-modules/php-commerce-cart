<?php

namespace Init\Commerce\Cart\Actions;

use Init\Commerce\Cart\Models\Cart;
use Init\Commerce\Cart\Models\CartItem;

class RemoveCartItem
{
    public function execute(CartItem $item): void
    {
        /** @var Cart|null $cart */
        $cart = $item->cart;

        $item->delete();

        $cart?->refreshAggregates();
    }
}
