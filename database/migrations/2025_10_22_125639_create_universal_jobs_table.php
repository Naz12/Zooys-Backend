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
        Schema::create('universal_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->unique();
            $table->string('tool_type');
            $table->json('input');
            $table->json('options')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('stage')->default('initializing');
            $table->integer('progress')->default(0);
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->json('logs')->nullable();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('universal_jobs');
    }
};
