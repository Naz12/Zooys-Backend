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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // Free, Pro, Premium
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD'); // ✅ currency
            $table->integer('limit')->nullable();           // ✅ monthly usage limit
            $table->boolean('is_active')->default(true);    // ✅ active/inactive
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};