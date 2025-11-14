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
        Schema::create('presentation_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('filename');
            $table->string('file_path'); // Path relative to storage: presentations/{user_id}/{filename}
            $table->bigInteger('file_size'); // File size in bytes
            $table->string('template')->nullable(); // Template used
            $table->string('color_scheme')->nullable();
            $table->string('font_style')->nullable();
            $table->integer('slides_count')->default(0);
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamp('expires_at'); // Auto-delete after 1 month
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presentation_files');
    }
};
