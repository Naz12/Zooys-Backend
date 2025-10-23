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
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->string('processing_status')->nullable()->after('is_processed');
            $table->json('processing_metadata')->nullable()->after('processing_status');
            $table->timestamp('processed_at')->nullable()->after('processing_metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->dropColumn(['processing_status', 'processing_metadata', 'processed_at']);
        });
    }
};
