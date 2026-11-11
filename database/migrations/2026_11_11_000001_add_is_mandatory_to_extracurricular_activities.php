<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('extracurricular_activity_definitions') && ! Schema::hasColumn('extracurricular_activity_definitions', 'is_mandatory')) {
            Schema::table('extracurricular_activity_definitions', function (Blueprint $table): void {
                $table->boolean('is_mandatory')->default(false)->after('name');
            });
        }

        if (Schema::hasTable('extracurricular_records') && Schema::hasColumn('extracurricular_records', 'predicate')) {
            Schema::table('extracurricular_records', function (Blueprint $table): void {
                $table->string('predicate', 30)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('extracurricular_activity_definitions') && Schema::hasColumn('extracurricular_activity_definitions', 'is_mandatory')) {
            Schema::table('extracurricular_activity_definitions', function (Blueprint $table): void {
                $table->dropColumn('is_mandatory');
            });
        }

        if (Schema::hasTable('extracurricular_records') && Schema::hasColumn('extracurricular_records', 'predicate')) {
            Schema::table('extracurricular_records', function (Blueprint $table): void {
                $table->string('predicate', 30)->nullable(false)->change();
            });
        }
    }
};
