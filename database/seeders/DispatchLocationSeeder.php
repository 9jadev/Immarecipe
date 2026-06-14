<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DispatchLocationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['name' => 'Afara', 'price' => 2000, 'is_active' => true, 'sort_order' => 1],
            ['name' => 'FMC', 'price' => 2000, 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Umudike', 'price' => 2000, 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Ahiaeke', 'price' => 1500, 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Amuzukwu', 'price' => 2000, 'is_active' => true, 'sort_order' => 5],
            ['name' => 'Umudike Junction', 'price' => 1500, 'is_active' => true, 'sort_order' => 6],
            ['name' => 'Living Spring Estate', 'price' => 1000, 'is_active' => true, 'sort_order' => 7],
            ['name' => 'Olokoro', 'price' => 2500, 'is_active' => true, 'sort_order' => 8],
            ['name' => 'Marcauly Street', 'price' => 2000, 'is_active' => true, 'sort_order' => 9],
            ['name' => 'Locust', 'price' => 2000, 'is_active' => true, 'sort_order' => 10],
            ['name' => 'Agbama', 'price' => 2000, 'is_active' => true, 'sort_order' => 11],
            ['name' => 'World Bank', 'price' => 1500, 'is_active' => true, 'sort_order' => 12],
            ['name' => 'Ubakala', 'price' => 3000, 'is_active' => true, 'sort_order' => 13],
            ['name' => 'Obioma Estate', 'price' => 1500, 'is_active' => true, 'sort_order' => 14],
            ['name' => 'Along Dozie Way', 'price' => 1500, 'is_active' => true, 'sort_order' => 15],
            ['name' => 'Along Dozie Way (Inside)', 'price' => 2000, 'is_active' => true, 'sort_order' => 16],
            ['name' => 'Commissioners Quarters', 'price' => 2000, 'is_active' => true, 'sort_order' => 17],
            ['name' => 'Isi Court', 'price' => 2500, 'is_active' => true, 'sort_order' => 18],
            ['name' => 'Govt College', 'price' => 1500, 'is_active' => true, 'sort_order' => 19],
            ['name' => 'IBB Estate', 'price' => 2000, 'is_active' => true, 'sort_order' => 20],
            ['name' => 'Umuobia', 'price' => 2500, 'is_active' => true, 'sort_order' => 21],
        ];

        $rows = array_map(function (array $row) use ($now) {
            return [
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $rows);

        DB::table('dispatch_locations')->upsert(
            $rows,
            ['name'],
            ['price', 'is_active', 'sort_order', 'updated_at']
        );
    }
}
