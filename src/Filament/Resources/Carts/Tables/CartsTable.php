<?php

namespace Init\Commerce\Cart\Filament\Resources\Carts\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Init\Commerce\Cart\Enums\CartStatus;

class CartsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('actor_type')
                    ->label('Тип актёра')
                    ->searchable(),

                TextColumn::make('actor_id')
                    ->label('Actor ID')
                    ->searchable()
                    ->copyable(),

                IconColumn::make('actor_authenticated')
                    ->label('Auth')
                    ->boolean(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),

                TextColumn::make('items_quantity')
                    ->label('Qty')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(CartStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
