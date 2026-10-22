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
        Schema::create('learning_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('sequence'); // TP/LM ke-
            $table->text('summary');
            $table->decimal('kktp', 5, 2);
            $table->timestamps();

            $table->unique(['teaching_assignment_id', 'sequence']);
        });

        Schema::create('summative_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_scope_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['learning_scope_id', 'student_id']);
        });

        Schema::create('sts_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['teaching_assignment_id', 'student_id']);
        });

        Schema::create('competency_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->text('description');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['teaching_assignment_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competency_overrides');
        Schema::dropIfExists('sts_scores');
        Schema::dropIfExists('summative_scores');
        Schema::dropIfExists('learning_scopes');
    }
};
