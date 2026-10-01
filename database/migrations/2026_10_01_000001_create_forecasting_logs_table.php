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
        Schema::create('forecasting_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->date('period_date');
            $table->decimal('alpha', 4, 3);
            $table->decimal('beta', 4, 3);
            $table->integer('actual_quantity')->nullable();
            $table->integer('forecast_quantity');
            $table->decimal('mape', 6, 2)->nullable();
            $table->decimal('rmse', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecasting_logs');
    }
};
