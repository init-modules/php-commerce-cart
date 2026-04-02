<?php

namespace Init\Commerce\Cart\Filament\Resources\Carts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Init\Commerce\Cart\Enums\CartStatus;

class CartForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->schema([
                    TextInput::make('actor_type')
                        ->label('Тип актёра')
                        ->disabled(),

                    TextInput::make('actor_id')
                        ->label('ID актёра')
                        ->disabled(),

                    Select::make('status')
                        ->label('Статус')
                        ->options(CartStatus::options())
                        ->required()
                        ->native(false),

                    TextInput::make('currency')
                        ->label('Валюта')
                        ->maxLength(3),

                    TextInput::make('item_count')
                        ->label('Кол-во позиций')
                        ->numeric()
                        ->disabled(),

                    TextInput::make('items_quantity')
                        ->label('Сумма quantity')
                        ->numeric()
                        ->disabled(),

                    TextInput::make('subtotal_amount')
                        ->label('Subtotal')
                        ->disabled(),

                    TextInput::make('total_amount')
                        ->label('Total')
                        ->disabled(),

                    DateTimePicker::make('last_activity_at')
                        ->label('Последняя активность')
                        ->seconds(false)
                        ->disabled(),

                    DateTimePicker::make('checked_out_at')
                        ->label('Checkout')
                        ->seconds(false)
                        ->disabled(),

                    KeyValue::make('meta')
                        ->label('Meta')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
