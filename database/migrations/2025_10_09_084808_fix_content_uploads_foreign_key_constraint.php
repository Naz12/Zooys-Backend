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
        // Drop document_metadata table first to remove foreign key constraint
        Schema::dropIfExists('document_metadata');
        
        // Now drop content_uploads table
        Schema::dropIfExists('content_uploads');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate content_uploads table
        Schema::create('content_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->timestamps();
        });
        
        // Recreate document_metadata table
        Schema::create('document_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('content_uploads')->onDelete('cascade');
            $table->integer('total_pages')->default(0);
            $table->integer('total_chunks')->default(0);
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique('document_id');
        });
    }
};
