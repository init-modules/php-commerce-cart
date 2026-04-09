<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_carts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor_type');
            $table->string('actor_id');
            $table->boolean('actor_authenticated')->default(false);
            $table->string('status')->index();
            $table->string('active_actor_key')->nullable()->unique();
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('items_quantity')->default(0);
            $table->bigInteger('subtotal_amount')->default(0);
            $table->bigInteger('discount_total_amount')->default(0);
            $table->bigInteger('tax_total_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->uuid('merged_into_cart_id')->nullable();
            $table->uuid('converted_order_id')->nullable();
            $table->timestamp('checked_out_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->json('pricing_context')->nullable();
            $table->json('discount_codes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['actor_type', 'actor_id', 'status'], 'commerce_carts_actor_status_idx');
        });

        Schema::table('commerce_carts', function (Blueprint $table): void {
            $table->foreign('merged_into_cart_id')
                ->references('id')
                ->on('commerce_carts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_carts');
    }
};
