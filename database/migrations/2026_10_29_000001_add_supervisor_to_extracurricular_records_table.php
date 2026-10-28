<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extracurricular_records', function (Blueprint $table): void {
            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('student_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(['period_id', 'supervisor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('extracurricular_records', function (Blueprint $table): void {
            $table->dropForeign(['supervisor_id']);
            $table->dropIndex(['period_id', 'supervisor_id']);
            $table->dropColumn('supervisor_id');
        });
    }
};
