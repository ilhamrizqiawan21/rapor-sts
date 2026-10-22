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
        Schema::create('madrasah_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('parent_institution')->nullable();
            $table->string('foundation_name')->nullable();
            $table->string('name');
            $table->string('level'); // MI, MTs, MA
            $table->string('nsm');
            $table->string('npsn');
            $table->string('address');
            $table->string('district');
            $table->string('regency');
            $table->string('province');
            $table->string('contact')->nullable();
            $table->string('headmaster_name');
            $table->string('headmaster_nip')->nullable();
            $table->string('logo_left_path')->nullable();
            $table->string('logo_right_path')->nullable();
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name');
            $table->string('app_subtitle')->nullable();
            $table->string('app_logo_path')->nullable();
            $table->string('login_image_path')->nullable();
            $table->string('topbar_color')->default('#628b0d');
            $table->decimal('default_kktp', 5, 2)->default(70.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('madrasah_profiles');
        Schema::dropIfExists('app_settings');
    }
};
