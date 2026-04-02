<?php

namespace Init\Commerce\Cart\Filament\Resources\Carts\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Init\Commerce\Cart\Filament\Resources\Carts\CartResource;

class EditCart extends EditRecord
{
    protected static string $resource = CartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
