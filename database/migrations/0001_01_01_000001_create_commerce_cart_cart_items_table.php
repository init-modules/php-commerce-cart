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
            $table->string('configuration_hash')->default('default');
            $table->bigInteger('base_price_amount')->default(0);
            $table->bigInteger('configuration_price_adjustment_amount')->default(0);
            $table->bigInteger('unit_subtotal_amount')->default(0);
            $table->bigInteger('unit_discount_amount')->default(0);
            $table->bigInteger('unit_tax_amount')->default(0);
            $table->bigInteger('unit_total_amount')->default(0);
            $table->bigInteger('line_subtotal_amount')->default(0);
            $table->bigInteger('line_discount_amount')->default(0);
            $table->bigInteger('line_tax_amount')->default(0);
            $table->bigInteger('line_total_amount')->default(0);
            $table->json('configuration_snapshot')->nullable();
            $table->json('pricing_snapshot')->nullable();
            $table->json('catalog_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(
                ['cart_id', 'catalog_item_type', 'catalog_item_id', 'configuration_hash'],
                'commerce_cart_items_cart_catalog_config_unique'
            );
            $table->index(['catalog_item_type', 'catalog_item_id'], 'commerce_cart_items_catalog_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_cart_items');
    }
};
