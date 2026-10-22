<?php

namespace Database\Seeders;

use App\Enums\Curriculum;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        // 1. PAI (Induk)
        $pai = Subject::firstOrCreate(
            ['curriculum' => Curriculum::Merdeka, 'code' => 'PAI'],
            [
                'name' => 'Pendidikan Agama Islam',
                'group' => 'umum',
                'sort_order' => 1,
                'is_active' => true,
                'parent_id' => null,
            ]
        );

        $paiChildren = [
            ['code' => 'QH', 'name' => "Al Qur'an Hadis", 'sort_order' => 2],
            ['code' => 'AA', 'name' => 'Akidah Akhlak', 'sort_order' => 3],
            ['code' => 'FIK', 'name' => 'Fikih', 'sort_order' => 4],
            ['code' => 'SKI', 'name' => 'Sejarah Kebudayaan Islam', 'sort_order' => 5],
        ];

        foreach ($paiChildren as $item) {
            Subject::firstOrCreate(
                ['curriculum' => Curriculum::Merdeka, 'code' => $item['code']],
                [
                    'name' => $item['name'],
                    'group' => 'umum',
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'parent_id' => $pai->id,
                ]
            );
        }

        // Mapel Mandiri lainnya
        $otherSubjects = [
            ['code' => 'BAR', 'name' => 'Bahasa Arab', 'sort_order' => 6],
            ['code' => 'PP', 'name' => 'Pendidikan Pancasila', 'sort_order' => 7],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'sort_order' => 8],
            ['code' => 'MTK', 'name' => 'Matematika', 'sort_order' => 9],
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'sort_order' => 10],
            ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'sort_order' => 11],
            ['code' => 'BIG', 'name' => 'Bahasa Inggris', 'sort_order' => 12],
            ['code' => 'PJOK', 'name' => 'Pendidikan Jasmani, Olahraga dan Kesehatan', 'sort_order' => 13],
            ['code' => 'INF', 'name' => 'Informatika', 'sort_order' => 14],
            ['code' => 'SBP', 'name' => 'Seni Budaya / Prakarya', 'sort_order' => 15],
            ['code' => 'MULOK', 'name' => 'Muatan Lokal', 'group' => 'mulok', 'sort_order' => 16],
        ];

        foreach ($otherSubjects as $item) {
            Subject::firstOrCreate(
                ['curriculum' => Curriculum::Merdeka, 'code' => $item['code']],
                [
                    'name' => $item['name'],
                    'group' => $item['group'] ?? 'umum',
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'parent_id' => null,
                ]
            );
        }
    }
}
