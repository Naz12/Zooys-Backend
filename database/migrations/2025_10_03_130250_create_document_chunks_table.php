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
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upload_id');
            $table->integer('chunk_index');
            $table->text('content');
            $table->json('embedding'); // Store vector as JSON
            $table->integer('page_start')->nullable();
            $table->integer('page_end')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('upload_id')->references('id')->on('content_uploads')->onDelete('cascade');
            $table->index(['upload_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
