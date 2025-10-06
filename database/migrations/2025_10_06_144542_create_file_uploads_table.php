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
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_name'); // Original filename
            $table->string('stored_name'); // Stored filename (UUID)
            $table->string('file_path'); // Full path to file
            $table->string('mime_type'); // MIME type
            $table->bigInteger('file_size'); // File size in bytes
            $table->string('file_type'); // pdf, doc, txt, audio, etc.
            $table->json('metadata')->nullable(); // Additional file metadata
            $table->boolean('is_processed')->default(false); // Whether file has been processed
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('file_type');
            $table->index('is_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
