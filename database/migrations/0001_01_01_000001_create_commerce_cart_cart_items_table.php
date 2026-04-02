<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_cart_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')
                ->constrained('commerce_carts')
                ->cascadeOnDelete();
            $table->string('catalog_item_type');
            $table->string('catalog_item_id');
            $table->string('item_name');
            $table->string('item_sku')->nullable()->index();
            $table->string('item_type')->nullable()->index();
            $table->unsignedInteger('quantity');
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_base_total', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->json('pricing_snapshot')->nullable();
            $table->json('catalog_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(
                ['cart_id', 'catalog_item_type', 'catalog_item_id'],
                'commerce_cart_items_cart_catalog_unique'
            );
            $table->index(['catalog_item_type', 'catalog_item_id'], 'commerce_cart_items_catalog_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_cart_items');
    }
};
