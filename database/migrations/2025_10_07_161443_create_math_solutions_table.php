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
        Schema::create('math_solutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('math_problem_id')->constrained()->cascadeOnDelete();
            $table->string('solution_method'); // algebraic, graphical, numerical, etc.
            $table->text('step_by_step_solution'); // Detailed step-by-step solution
            $table->string('final_answer');
            $table->text('explanation')->nullable(); // Additional explanation
            $table->json('verification')->nullable(); // Verification steps
            $table->json('metadata')->nullable(); // Additional solution metadata
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('math_solutions');
    }
};
