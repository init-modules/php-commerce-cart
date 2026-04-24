<?php

namespace Init\Commerce\Cart\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CatalogItemResolver
{
    public function resolve(Model|string $catalogItem, ?string $catalogItemType = null): Model
    {
        if ($catalogItem instanceof Model) {
            $this->ensureAllowedModelClass($catalogItem::class);

            return $catalogItem;
        }

        $modelClass = $this->resolveModelClass($catalogItemType);

        return $modelClass::query()->findOrFail($catalogItem);
    }

    public function resolveByReference(string $catalogItemType, string $catalogItemId): ?Model
    {
        try {
            $modelClass = $this->resolveModelClass($catalogItemType);
        } catch (ValidationException) {
            return null;
        }

        return $modelClass::query()->find($catalogItemId);
    }

    public function makeCartLinePayload(Model $catalogItem): array
    {
        $catalogSnapshot = $this->makeCatalogSnapshot($catalogItem);
        $pricingSnapshot = $this->makePricingSnapshot($catalogItem);

        return [
            'catalog_item_type' => $catalogItem::class,
            'catalog_item_id' => (string) $catalogItem->getKey(),
            'item_name' => $catalogSnapshot['name'],
            'item_sku' => $catalogSnapshot['sku'],
            'item_type' => $catalogSnapshot['item_type'],
            'base_price_amount' => $this->integerAmount($pricingSnapshot['base_price']),
            'unit_subtotal_amount' => $this->integerAmount($pricingSnapshot['base_price']),
            'unit_total_amount' => $this->integerAmount($pricingSnapshot['unit_price']),
            'pricing_snapshot' => $pricingSnapshot,
            'catalog_snapshot' => $catalogSnapshot,
        ];
    }

    public function makeCatalogSnapshot(Model $catalogItem): array
    {
        return [
            'id' => (string) $catalogItem->getKey(),
            'type' => $catalogItem::class,
            'name' => (string) ($this->extractAttribute($catalogItem, 'name') ?? $catalogItem->getKey()),
            'sku' => $this->stringOrNull($this->extractAttribute($catalogItem, 'sku')),
            'item_type' => $this->stringOrNull($this->extractAttribute($catalogItem, 'type')),
            'tracked' => (bool) ($this->extractAttribute($catalogItem, 'tracked') ?? false),
            'cover_image' => $this->stringOrNull(
                $this->extractAttribute($catalogItem, 'cover_image') ?? $this->extractCoverImage($catalogItem)
            ),
        ];
    }

    public function makePricingSnapshot(Model $catalogItem): array
    {
        $basePrice = $this->decimal($this->extractAttribute($catalogItem, 'base_price') ?? 0);
        $unitPrice = $this->decimal($this->extractAttribute($catalogItem, 'unit_price') ?? $basePrice);

        return [
            'base_price' => $basePrice,
            'unit_price' => $unitPrice,
            'currency' => $this->stringOrNull(
                $this->extractAttribute($catalogItem, 'currency')
            ) ?? config('commerce_cart.pricing.default_currency'),
            'captured_at' => now()->toIso8601String(),
        ];
    }

    private function extractAttribute(Model $catalogItem, string $key): mixed
    {
        $attributes = (array) config("commerce_cart.catalog_item.attributes.{$key}", []);

        foreach ($attributes as $attribute) {
            $value = $catalogItem->getAttribute($attribute);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractCoverImage(Model $catalogItem): mixed
    {
        if (method_exists($catalogItem, 'getFirstMediaUrl')) {
            $url = $catalogItem->getFirstMediaUrl('cover');

            if ($url !== null && $url !== '') {
                return $url;
            }
        }

        $meta = $catalogItem->getAttribute('meta');

        if (is_array($meta)) {
            return data_get($meta, 'cover_image')
                ?? data_get($meta, 'coverImage')
                ?? data_get($meta, 'image')
                ?? data_get($meta, 'image_url')
                ?? data_get($meta, 'thumbnail')
                ?? data_get($meta, 'thumbnail_url');
        }

        return null;
    }

    private function decimal(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function integerAmount(mixed $value): int
    {
        return (int) round((float) $value);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * @return list<class-string<Model>>
     */
    private function allowedModelClasses(): array
    {
        $configuredModel = config('commerce_cart.models.catalog_item');
        $allowedModels = config('commerce_cart.catalog_item.allowed_models', [$configuredModel]);

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $model): ?string => is_string($model) ? $model : null, (array) $allowedModels),
            static fn (?string $model): bool => $model !== null,
        )));
    }

    /**
     * @return class-string<Model>
     */
    private function resolveModelClass(?string $catalogItemType = null): string
    {
        $modelClass = $catalogItemType ?: config('commerce_cart.models.catalog_item');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            throw ValidationException::withMessages([
                'catalog_item_type' => ['Configured catalog item model is not available.'],
            ]);
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            throw ValidationException::withMessages([
                'catalog_item_type' => ['Configured catalog item model must extend Eloquent Model.'],
            ]);
        }

        $this->ensureAllowedModelClass($modelClass);

        return $modelClass;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function ensureAllowedModelClass(string $modelClass): void
    {
        if (in_array($modelClass, $this->allowedModelClasses(), true)) {
            return;
        }

        throw ValidationException::withMessages([
            'catalog_item_type' => ['Selected catalog item type is not allowed.'],
        ]);
    }
}
