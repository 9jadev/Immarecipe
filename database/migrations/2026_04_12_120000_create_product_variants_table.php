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
        // Product Variant Options (e.g., Size, Color, Material)
        Schema::create('product_variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Size", "Color"
            $table->integer('position')->default(0); // For ordering
            $table->timestamps();
        });

        // Product Variant Values (e.g., Small, Medium, Red, Blue)
        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_option_id')->constrained('product_variant_options')->onDelete('cascade');
            $table->string('value'); // e.g., "Small", "Red"
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        // Product Variants (actual SKUs with specific combinations)
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('variant_name'); // e.g., "Small / Red"
            $table->decimal('price', 10, 2)->nullable(); // Null = use parent product price
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->integer('stock_count')->default(0);
            $table->enum('stock_status', ['in_stock', 'low_stock', 'out_of_stock'])->default('in_stock');
            $table->json('images')->nullable(); // Variant-specific images
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pivot table linking variants to their values
        Schema::create('product_variant_combinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('variant_value_id')->constrained('product_variant_values')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_combinations');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variant_options');
    }
};
