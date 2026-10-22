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
        Schema::create('print_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->string('report_title')->default('LAPORAN HASIL SUMATIF TENGAH SEMESTER');
            $table->string('print_place')->default('Bandung Barat');
            $table->date('print_date');
            $table->string('score_label')->default('Nilai STS');
            $table->string('paper_size')->default('A4'); // A4, F4
            $table->string('watermark_text')->nullable();
            $table->json('sections')->nullable(); // flag tampil per bagian (mis. jumlah nilai, catatan wali, tanggapan ortu)
            $table->timestamps();

            $table->unique('period_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_settings');
    }
};
