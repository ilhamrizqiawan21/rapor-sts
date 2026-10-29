<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('extracurricular_activities')) {
            Schema::create('extracurricular_activities', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('period_id')->constrained()->cascadeOnDelete();
                $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['period_id', 'classroom_id', 'name']);
                $table->index(['period_id', 'supervisor_id']);
            });
        }

        if (! Schema::hasColumn('extracurricular_records', 'extracurricular_activity_id')) {
            Schema::table('extracurricular_records', function (Blueprint $table): void {
                $table->foreignId('extracurricular_activity_id')
                    ->nullable()
                    ->after('supervisor_id')
                    ->constrained('extracurricular_activities')
                    ->nullOnDelete();
            });
        }

        $activityIndexName = 'extracurricular_records_period_activity_idx';
        $hasActivityIndex = collect(Schema::getIndexes('extracurricular_records'))
            ->contains(fn (array $index): bool => $index['name'] === $activityIndexName);

        if (! $hasActivityIndex) {
            Schema::table('extracurricular_records', function (Blueprint $table) use ($activityIndexName): void {
                $table->index(['period_id', 'extracurricular_activity_id'], $activityIndexName);
            });
        }

        $records = DB::table('extracurricular_records')
            ->select(['id', 'period_id', 'classroom_id', 'name', 'supervisor_id'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (object $record): string => $record->period_id.'|'.$record->classroom_id.'|'.mb_strtolower(trim($record->name)));

        foreach ($records as $activityRecords) {
            $first = $activityRecords->first();
            $supervisorId = $activityRecords->first(fn (object $record): bool => $record->supervisor_id !== null)?->supervisor_id;
            $activityId = DB::table('extracurricular_activities')->insertGetId([
                'period_id' => $first->period_id,
                'classroom_id' => $first->classroom_id,
                'name' => trim($first->name),
                'supervisor_id' => $supervisorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('extracurricular_records')
                ->whereIn('id', $activityRecords->pluck('id'))
                ->update([
                    'extracurricular_activity_id' => $activityId,
                    'supervisor_id' => $supervisorId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('extracurricular_records', function (Blueprint $table): void {
            $table->dropForeign(['extracurricular_activity_id']);
            $table->dropIndex('extracurricular_records_period_activity_idx');
            $table->dropColumn('extracurricular_activity_id');
        });

        Schema::dropIfExists('extracurricular_activities');
    }
};
