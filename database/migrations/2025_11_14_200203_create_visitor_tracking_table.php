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
        Schema::create('visitor_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('tool_id')->nullable()->index();
            $table->string('route_path')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('public_id')->index();
            $table->string('session_id')->index();
            $table->timestamp('visited_at')->useCurrent();
            $table->string('referrer')->nullable();
            
            // Location data (stored as JSON)
            $table->json('location')->nullable();
            
            // Additional metadata
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for better query performance
            $table->index(['public_id', 'visited_at']);
            $table->index(['session_id', 'visited_at']);
            $table->index(['tool_id', 'visited_at']);
            $table->index('visited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_tracking');
    }
};
