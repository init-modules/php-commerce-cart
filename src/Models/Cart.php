<?php

namespace Init\Commerce\Cart\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Init\Commerce\Cart\Enums\CartStatus;
use Init\VisitorSession\Support\ResolvedActor;

class Cart extends Model
{
    use HasUuids;

    protected $table = 'commerce_carts';

    protected $fillable = [
        'actor_type',
        'actor_id',
        'actor_authenticated',
        'status',
        'active_actor_key',
        'currency',
        'item_count',
        'items_quantity',
        'subtotal_amount',
        'total_amount',
        'merged_into_cart_id',
        'converted_order_id',
        'checked_out_at',
        'last_activity_at',
        'meta',
    ];

    protected $casts = [
        'actor_authenticated' => 'boolean',
        'status' => CartStatus::class,
        'item_count' => 'integer',
        'items_quantity' => 'integer',
        'subtotal_amount' => 'integer',
        'total_amount' => 'integer',
        'checked_out_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Cart $cart): void {
            $cart->active_actor_key = $cart->status === CartStatus::ACTIVE
                ? static::makeActiveActorKey($cart->actor_type, $cart->actor_id)
                : null;

            $cart->currency ??= config('commerce_cart.pricing.default_currency');
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            config('commerce_cart.models.cart_item', CartItem::class),
            'cart_id',
        );
    }

    public function mergedIntoCart(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_cart_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CartStatus::ACTIVE->value);
    }

    public function scopeForActor(Builder $query, ResolvedActor $actor): Builder
    {
        return $query
            ->where('actor_type', $actor->type)
            ->where('actor_id', $actor->id);
    }

    public function isOwnedBy(ResolvedActor $actor): bool
    {
        return $this->actor_type === $actor->type && $this->actor_id === $actor->id;
    }

    public function refreshAggregates(): static
    {
        $this->load('items');

        $itemCount = $this->items->count();
        $itemsQuantity = $this->items->sum('quantity');
        $subtotalAmount = $this->items->sum(fn (CartItem $item): float => (float) $item->line_subtotal_amount);
        $totalAmount = $this->items->sum(fn (CartItem $item): float => (float) $item->line_total_amount);

        $this->forceFill([
            'item_count' => $itemCount,
            'items_quantity' => $itemsQuantity,
            'subtotal_amount' => (int) round($subtotalAmount),
            'total_amount' => (int) round($totalAmount),
            'last_activity_at' => now(),
        ])->saveQuietly();

        return $this->fresh('items') ?? $this->load('items');
    }

    public static function makeActiveActorKey(ResolvedActor|string $actor, ?string $actorId = null): string
    {
        if ($actor instanceof ResolvedActor) {
            return $actor->type.':'.$actor->id;
        }

        return $actor.':'.(string) $actorId;
    }
}
