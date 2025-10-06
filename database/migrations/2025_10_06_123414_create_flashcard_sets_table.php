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
        Schema::create('flashcard_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('input_type'); // text, url, youtube, file
            $table->text('input_content'); // original input content
            $table->string('difficulty')->default('intermediate'); // beginner, intermediate, advanced
            $table->string('style')->default('mixed'); // definition, application, analysis, comparison, mixed
            $table->integer('total_cards');
            $table->json('source_metadata')->nullable(); // metadata about the source content
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashcard_sets');
    }
};
