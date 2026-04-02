<?php

namespace Init\Commerce\Cart\Filament\Resources\Carts\Pages;

use Filament\Resources\Pages\ListRecords;
use Init\Commerce\Cart\Filament\Resources\Carts\CartResource;

class ListCarts extends ListRecords
{
    protected static string $resource = CartResource::class;
}
