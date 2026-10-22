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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('group')->default('umum'); // umum, mulok
            $table->string('curriculum')->default('merdeka'); // merdeka, k13
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['curriculum', 'code']);
        });

        Schema::create('subject_kktp_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('level');
            $table->decimal('kktp', 5, 2);
            $table->timestamps();

            $table->unique(['academic_year_id', 'subject_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_kktp_defaults');
        Schema::dropIfExists('subjects');
    }
};
