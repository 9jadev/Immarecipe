<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();

        DB::table('dispatch_locations')->insert([
            ['name' => 'Afara', 'price' => 2000, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'FMC', 'price' => 2000, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Umudike', 'price' => 2000, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ahiaeke', 'price' => 1500, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Amuzukwu', 'price' => 2000, 'is_active' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Umudike Junction', 'price' => 1500, 'is_active' => true, 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Living Spring Estate', 'price' => 1000, 'is_active' => true, 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Olokoro', 'price' => 2500, 'is_active' => true, 'sort_order' => 8, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Marcauly Street', 'price' => 2000, 'is_active' => true, 'sort_order' => 9, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Locust', 'price' => 2000, 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Agbama', 'price' => 2000, 'is_active' => true, 'sort_order' => 11, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'World Bank', 'price' => 1500, 'is_active' => true, 'sort_order' => 12, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ubakala', 'price' => 3000, 'is_active' => true, 'sort_order' => 13, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obioma Estate', 'price' => 1500, 'is_active' => true, 'sort_order' => 14, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Along Dozie Way', 'price' => 1500, 'is_active' => true, 'sort_order' => 15, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Along Dozie Way (Inside)', 'price' => 2000, 'is_active' => true, 'sort_order' => 16, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Commissioners Quarters', 'price' => 2000, 'is_active' => true, 'sort_order' => 17, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Isi Court', 'price' => 2500, 'is_active' => true, 'sort_order' => 18, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Govt College', 'price' => 1500, 'is_active' => true, 'sort_order' => 19, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'IBB Estate', 'price' => 2000, 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Umuobia', 'price' => 2500, 'is_active' => true, 'sort_order' => 21, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_locations');
    }
};
