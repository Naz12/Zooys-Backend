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
        Schema::table('math_problems', function (Blueprint $table) {
            $table->unsignedBigInteger('file_upload_id')->nullable()->after('problem_image');
            $table->foreign('file_upload_id')->references('id')->on('file_uploads')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('math_problems', function (Blueprint $table) {
            $table->dropForeign(['file_upload_id']);
            $table->dropColumn('file_upload_id');
        });
    }
};
