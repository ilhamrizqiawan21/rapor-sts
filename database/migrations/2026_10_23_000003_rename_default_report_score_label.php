<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_settings', function (Blueprint $table) {
            $table->string('score_label')->default('Nilai Akhir')->change();
        });

        DB::table('print_settings')
            ->where('score_label', 'Nilai STS')
            ->update(['score_label' => 'Nilai Akhir']);
    }

    public function down(): void
    {
        Schema::table('print_settings', function (Blueprint $table) {
            $table->string('score_label')->default('Nilai STS')->change();
        });
    }
};
