<?php

namespace Init\Commerce\Cart\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CartItem extends Model
{
    use HasUuids;

    protected $table = 'commerce_cart_items';

    protected $fillable = [
        'cart_id',
        'catalog_item_type',
        'catalog_item_id',
        'item_name',
        'item_sku',
        'item_type',
        'quantity',
        'base_price',
        'unit_price',
        'line_base_total',
        'line_total',
        'pricing_snapshot',
        'catalog_snapshot',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'base_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_base_total' => 'decimal:2',
        'line_total' => 'decimal:2',
        'pricing_snapshot' => 'array',
        'catalog_snapshot' => 'array',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (CartItem $item): void {
            $basePrice = (float) $item->base_price;
            $unitPrice = (float) $item->unit_price;
            $quantity = max(0, (int) $item->quantity);

            $item->line_base_total = number_format($basePrice * $quantity, 2, '.', '');
            $item->line_total = number_format($unitPrice * $quantity, 2, '.', '');
        });

        static::saved(function (CartItem $item): void {
            $item->cart?->refreshAggregates();
        });

        static::deleted(function (CartItem $item): void {
            $item->cart?->refreshAggregates();
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(
            config('commerce_cart.models.cart', Cart::class),
            'cart_id',
        );
    }

    public function catalogItem(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'catalog_item_type', 'catalog_item_id');
    }
}
