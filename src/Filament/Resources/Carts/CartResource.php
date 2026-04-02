<?php

namespace Init\Commerce\Cart\Filament\Resources\Carts;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Init\Commerce\Catalog\Filament\Cluster\CommerceCluster;
use Init\Commerce\Cart\Filament\Resources\Carts\Pages\EditCart;
use Init\Commerce\Cart\Filament\Resources\Carts\Pages\ListCarts;
use Init\Commerce\Cart\Filament\Resources\Carts\RelationManagers\CartItemsRelationManager;
use Init\Commerce\Cart\Filament\Resources\Carts\Schemas\CartForm;
use Init\Commerce\Cart\Filament\Resources\Carts\Tables\CartsTable;
use Init\Commerce\Cart\Models\Cart;

class CartResource extends Resource
{
    protected static ?string $model = Cart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $cluster = CommerceCluster::class;

    protected static ?string $slug = 'carts';

    protected static ?string $navigationLabel = 'Корзины';

    protected static ?string $modelLabel = 'Корзина';

    protected static ?string $pluralModelLabel = 'Корзины';

    protected static ?int $navigationSort = 40;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return CartForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CartsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CartItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCarts::route('/'),
            'edit' => EditCart::route('/{record}/edit'),
        ];
    }
}
