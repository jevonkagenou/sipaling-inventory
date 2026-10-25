<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restock_request_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('restock_request_id')->constrained('restock_requests')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('current_stock')->default(0);
            $table->integer('safety_stock')->default(0);
            $table->integer('suggested_quantity')->default(0);
            $table->integer('requested_quantity')->default(1);
            $table->integer('approved_quantity')->nullable();
            $table->decimal('estimated_unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restock_request_details');
    }
};
