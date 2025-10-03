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
        Schema::table('content_uploads', function (Blueprint $table) {
            $table->timestamp('rag_processed_at')->nullable();
            $table->integer('chunk_count')->default(0);
            $table->boolean('rag_enabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_uploads', function (Blueprint $table) {
            $table->dropColumn(['rag_processed_at', 'chunk_count', 'rag_enabled']);
        });
    }
};
