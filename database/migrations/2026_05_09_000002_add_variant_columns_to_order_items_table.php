<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('order_items', 'product_variant_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedBigInteger('product_variant_id')->nullable();
            });
        }

        if (!Schema::hasColumn('order_items', 'variant_name')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('variant_name')->nullable();
            });
        }
    }

    public function down(): void
    {
        $columnsToDrop = [];
        if (Schema::hasColumn('order_items', 'product_variant_id')) {
            $columnsToDrop[] = 'product_variant_id';
        }
        if (Schema::hasColumn('order_items', 'variant_name')) {
            $columnsToDrop[] = 'variant_name';
        }

        if ($columnsToDrop) {
            Schema::table('order_items', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
