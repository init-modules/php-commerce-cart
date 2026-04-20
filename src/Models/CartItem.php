<?php

namespace Init\Commerce\Cart\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'configuration_hash',
        'base_price_amount',
        'configuration_price_adjustment_amount',
        'unit_subtotal_amount',
        'unit_discount_amount',
        'unit_tax_amount',
        'unit_total_amount',
        'line_subtotal_amount',
        'line_discount_amount',
        'line_tax_amount',
        'line_total_amount',
        'base_price',
        'unit_price',
        'line_base_total',
        'line_total',
        'configuration_snapshot',
        'pricing_snapshot',
        'catalog_snapshot',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'base_price_amount' => 'integer',
        'configuration_price_adjustment_amount' => 'integer',
        'unit_subtotal_amount' => 'integer',
        'unit_discount_amount' => 'integer',
        'unit_tax_amount' => 'integer',
        'unit_total_amount' => 'integer',
        'line_subtotal_amount' => 'integer',
        'line_discount_amount' => 'integer',
        'line_tax_amount' => 'integer',
        'line_total_amount' => 'integer',
        'configuration_snapshot' => 'array',
        'pricing_snapshot' => 'array',
        'catalog_snapshot' => 'array',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (CartItem $item): void {
            $basePrice = (float) $item->base_price_amount;
            $unitSubtotal = (float) $item->unit_subtotal_amount;
            $unitTotal = (float) $item->unit_total_amount;
            $quantity = max(0, (int) $item->quantity);

            if ($unitSubtotal <= 0 && ($unitTotal > 0 || $basePrice > 0)) {
                $unitSubtotal = $unitTotal > 0 ? $unitTotal : $basePrice;
                $item->unit_subtotal_amount = (int) round($unitSubtotal);
            }

            if ($unitTotal <= 0 && ($unitSubtotal > 0 || $basePrice > 0)) {
                $unitTotal = $unitSubtotal > 0 ? $unitSubtotal : $basePrice;
                $item->unit_total_amount = (int) round($unitTotal);
            }

            $item->line_subtotal_amount = (int) round($unitSubtotal * $quantity);
            $item->line_total_amount = (int) round($unitTotal * $quantity);
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

    protected function basePrice(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format((float) $this->base_price_amount, 2, '.', ''),
            set: fn (mixed $value): array => [
                'base_price_amount' => (int) round((float) $value),
            ],
        );
    }

    protected function unitPrice(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format((float) $this->unit_total_amount, 2, '.', ''),
            set: fn (mixed $value): array => [
                'unit_subtotal_amount' => (int) round((float) $value),
                'unit_total_amount' => (int) round((float) $value),
            ],
        );
    }

    protected function lineBaseTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format((float) $this->line_subtotal_amount, 2, '.', ''),
            set: fn (mixed $value): array => [
                'line_subtotal_amount' => (int) round((float) $value),
            ],
        );
    }

    protected function lineTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): string => number_format((float) $this->line_total_amount, 2, '.', ''),
            set: fn (mixed $value): array => [
                'line_total_amount' => (int) round((float) $value),
            ],
        );
    }
}
