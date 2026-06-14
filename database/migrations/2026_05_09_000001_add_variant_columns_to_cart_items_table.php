<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($rows) > 0;
        }

        if ($driver === 'pgsql') {
            $rows = DB::select(
                "select 1 from pg_indexes where schemaname = current_schema() and tablename = ? and indexname = ? limit 1",
                [$table, $indexName]
            );
            return count($rows) > 0;
        }

        return false;
    }

    public function up(): void
    {
        if (!Schema::hasColumn('cart_items', 'product_variant_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unsignedBigInteger('product_variant_id')->nullable();
            });
        }

        if (!Schema::hasColumn('cart_items', 'variant_name')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->string('variant_name')->nullable();
            });
        }

        if ($this->indexExists('cart_items', 'cart_items_cart_id_product_id_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique(['cart_id', 'product_id']);
            });
        }

        if (!$this->indexExists('cart_items', 'cart_items_cart_id_product_id_product_variant_id_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_id', 'product_variant_id']);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('cart_items', 'cart_items_cart_id_product_id_product_variant_id_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique(['cart_id', 'product_id', 'product_variant_id']);
            });
        }

        $columnsToDrop = [];
        if (Schema::hasColumn('cart_items', 'product_variant_id')) {
            $columnsToDrop[] = 'product_variant_id';
        }
        if (Schema::hasColumn('cart_items', 'variant_name')) {
            $columnsToDrop[] = 'variant_name';
        }

        if ($columnsToDrop) {
            Schema::table('cart_items', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        if (!$this->indexExists('cart_items', 'cart_items_cart_id_product_id_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unique(['cart_id', 'product_id']);
            });
        }
    }
};
