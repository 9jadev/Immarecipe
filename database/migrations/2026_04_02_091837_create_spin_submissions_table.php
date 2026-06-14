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
        Schema::create('spin_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->integer('discount_won');
            $table->string('discount_code');
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('spin_settings', function (Blueprint $table) {
            $table->id();
            $table->json('discounts'); // Array of {percentage, label, color, probability}
            $table->boolean('allow_no_discount')->default(true);
            $table->integer('max_spins_per_email')->default(1);
            $table->integer('code_expiry_hours')->default(24);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spin_settings');
        Schema::dropIfExists('spin_submissions');
    }
};
