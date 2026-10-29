<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracurricular_activity_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('period_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period_id', 'name']);
            $table->index(['period_id', 'supervisor_id'], 'extra_definitions_period_supervisor_idx');
        });

        Schema::table('extracurricular_records', function (Blueprint $table): void {
            $table->foreignId('activity_definition_id')
                ->nullable()
                ->after('extracurricular_activity_id')
                ->constrained('extracurricular_activity_definitions')
                ->nullOnDelete();
            $table->index(['period_id', 'activity_definition_id'], 'extra_records_period_definition_idx');
        });

        $activities = DB::table('extracurricular_activities')
            ->select(['id', 'period_id', 'name', 'supervisor_id'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (object $activity): string => $activity->period_id.'|'.mb_strtolower(trim($activity->name)));

        foreach ($activities as $activityRows) {
            $first = $activityRows->first();
            $supervisorId = $activityRows->first(fn (object $activity): bool => $activity->supervisor_id !== null)?->supervisor_id;
            $definitionId = DB::table('extracurricular_activity_definitions')->insertGetId([
                'period_id' => $first->period_id,
                'name' => trim($first->name),
                'supervisor_id' => $supervisorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('extracurricular_records')
                ->whereIn('extracurricular_activity_id', $activityRows->pluck('id'))
                ->update([
                    'activity_definition_id' => $definitionId,
                    'supervisor_id' => $supervisorId,
                    'name' => trim($first->name),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('extracurricular_records', function (Blueprint $table): void {
            $table->dropForeign(['activity_definition_id']);
            $table->dropIndex('extra_records_period_definition_idx');
            $table->dropColumn('activity_definition_id');
        });

        Schema::dropIfExists('extracurricular_activity_definitions');
    }
};
