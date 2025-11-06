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
        // Add doc_id to file_uploads table
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->string('doc_id')->nullable()->after('is_processed');
            $table->index('doc_id');
        });

        // Add doc_id to ai_results table
        Schema::table('a_i_results', function (Blueprint $table) {
            $table->string('doc_id')->nullable()->after('file_upload_id');
            $table->index('doc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->dropIndex(['doc_id']);
            $table->dropColumn('doc_id');
        });

        Schema::table('a_i_results', function (Blueprint $table) {
            $table->dropIndex(['doc_id']);
            $table->dropColumn('doc_id');
        });
    }
};
