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
        Schema::create('document_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_id')->unique(); // Format: "zooys.{random_id}"
            $table->string('doc_id')->index(); // Links to Document Intelligence doc_id
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Indexes for quick lookups
            $table->index(['doc_id', 'user_id']);
            $table->index('conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_conversations');
    }
};
