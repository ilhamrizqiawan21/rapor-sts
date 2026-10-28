<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievement_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('level', 30);
            $table->string('rank', 50)->nullable();
            $table->date('achieved_at')->nullable();
            $table->string('organizer', 150)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['period_id', 'student_id', 'name']);
            $table->index(['period_id', 'classroom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_records');
    }
};
