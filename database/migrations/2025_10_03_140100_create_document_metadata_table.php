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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_metadata');
    }
};
