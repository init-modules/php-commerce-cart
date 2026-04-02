<?php

namespace Init\Commerce\Cart\Actions;

use Illuminate\Support\Facades\DB;
use Init\Commerce\Cart\Enums\CartStatus;
use Init\Commerce\Cart\Models\Cart;
use Init\Commerce\Cart\Models\CartItem;
use Init\VisitorSession\Support\ResolvedActor;

class MergeActorCarts
{
    public function __construct(
        private readonly ResolveActiveCart $resolveActiveCart,
    ) {}

    public function execute(ResolvedActor $sourceActor, ResolvedActor $targetActor): Cart
    {
        if (
            $sourceActor->type === $targetActor->type
            && $sourceActor->id === $targetActor->id
        ) {
            return $this->resolveActiveCart->execute($targetActor, createIfMissing: true);
        }

        return DB::transaction(function () use ($sourceActor, $targetActor): Cart {
            $sourceCart = $this->resolveActiveCart->execute($sourceActor, createIfMissing: false);
            $targetCart = $this->resolveActiveCart->execute($targetActor, createIfMissing: true);

            if (! $sourceCart instanceof Cart || $sourceCart->is($targetCart)) {
                return $targetCart->load('items');
            }

            $sourceCart->loadMissing('items');
            $targetCart->loadMissing('items');

            foreach ($sourceCart->items as $sourceItem) {
                $targetItem = $targetCart->items
                    ->first(fn (CartItem $candidate): bool => $candidate->catalog_item_type === $sourceItem->catalog_item_type
                        && $candidate->catalog_item_id === $sourceItem->catalog_item_id);

                if ($targetItem instanceof CartItem) {
                    $targetItem->quantity += $sourceItem->quantity;

                    if ($sourceItem->updated_at?->gt($targetItem->updated_at)) {
                        $targetItem->fill([
                            'item_name' => $sourceItem->item_name,
                            'item_sku' => $sourceItem->item_sku,
                            'item_type' => $sourceItem->item_type,
                            'base_price' => $sourceItem->base_price,
                            'unit_price' => $sourceItem->unit_price,
                            'pricing_snapshot' => $sourceItem->pricing_snapshot,
                            'catalog_snapshot' => $sourceItem->catalog_snapshot,
                            'meta' => $sourceItem->meta,
                        ]);
                    }

                    $targetItem->save();
                    $sourceItem->delete();
                    continue;
                }

                $sourceItem->forceFill([
                    'cart_id' => $targetCart->getKey(),
                ])->save();
            }

            $sourceCart->forceFill([
                'status' => CartStatus::MERGED,
                'merged_into_cart_id' => $targetCart->getKey(),
                'active_actor_key' => null,
                'last_activity_at' => now(),
            ])->save();

            $targetCart->forceFill([
                'last_activity_at' => now(),
            ])->saveQuietly();

            $sourceCart->refreshAggregates();
            $targetCart->refreshAggregates();

            return $targetCart->fresh('items');
        });
    }
}
