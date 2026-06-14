<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('dispatch_location_id')
                ->nullable()
                ->after('city')
                ->constrained('dispatch_locations')
                ->nullOnDelete();
            $table->string('dispatch_location_name')->nullable()->after('dispatch_location_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dispatch_location_id');
            $table->dropColumn('dispatch_location_name');
        });
    }
};
