<?php

namespace Init\Commerce\Cart\Filament\Resources\Carts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class CartItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Позиции корзины';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->schema([
                    TextInput::make('item_name')
                        ->label('Название')
                        ->disabled(),

                    TextInput::make('item_sku')
                        ->label('SKU')
                        ->disabled(),

                    TextInput::make('item_type')
                        ->label('Тип')
                        ->disabled(),

                    TextInput::make('quantity')
                        ->label('Количество')
                        ->disabled(),

                    TextInput::make('base_price')
                        ->label('Base price')
                        ->disabled(),

                    TextInput::make('unit_price')
                        ->label('Unit price')
                        ->disabled(),

                    KeyValue::make('pricing_snapshot')
                        ->label('Pricing snapshot')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('item_name')
                    ->label('Название')
                    ->searchable()
                    ->description(fn ($record): string => implode(' / ', array_filter([
                        $record->item_sku,
                        $record->item_type,
                    ]))),

                TextColumn::make('quantity')
                    ->label('Qty'),

                TextColumn::make('unit_price')
                    ->label('Цена'),

                TextColumn::make('line_total')
                    ->label('Сумма'),

                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->since(),
            ]);
    }
}
