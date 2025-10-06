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
        Schema::create('a_i_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('file_upload_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('tool_type'); // flashcards, presentation, summary, etc.
            $table->string('title'); // Result title
            $table->text('description')->nullable(); // Result description
            $table->json('input_data'); // Original input data
            $table->json('result_data'); // AI generated result
            $table->json('metadata')->nullable(); // Additional metadata
            $table->string('status')->default('completed'); // completed, processing, failed
            $table->timestamps();
            
            $table->index(['user_id', 'tool_type']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_i_results');
    }
};
