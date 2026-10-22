<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Versi Template RDM Bawaan
    |--------------------------------------------------------------------------
    */
    'default_version' => '3.1',

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Pemetaan Kolom & Sheet per Versi RDM
    |--------------------------------------------------------------------------
    | Setiap versi RDM mendefinisikan posisi sel, baris, dan kolom untuk
    | template Sumatif Lingkup Materi dan Tes (PAT/PAS/STS).
    */
    'versions' => [
        '3.1' => [
            'name' => 'RDM v3.1 (Kurikulum Merdeka)',

            'sumatif' => [
                'template_path' => base_path('reference/rdm-template/rdm_template_sumatif_v3.1.xlsx'),
                'sheet_prefix' => 'SUM ',
                'max_sheets' => 15,
                'header_row' => 6,
                'start_row' => 7,
                'col_no' => 'A',
                'col_id_siswa' => 'B',
                'col_nis' => 'C',
                'col_nisn' => 'D',
                'col_nama' => 'E',
                'col_nilai' => 'F',
                'cell_title' => 'A1',
                'cell_name' => 'B2',
                'cell_class_subject' => 'E2',
                'cell_topic' => 'B3',
            ],

            'tes' => [
                'template_path' => base_path('reference/rdm-template/rdm_template_pat_v3.1.xlsx'),
                'sheet_names' => ['PAT 1', 'PAS 1', 'STS 1', 'SAS 1'],
                'header_row' => 6,
                'start_row' => 7,
                'col_no' => 'A',
                'col_id_siswa' => 'B',
                'col_nis' => 'C',
                'col_nisn' => 'D',
                'col_nama' => 'E',
                'col_nilai' => 'F',
                'cell_title' => 'A1',
                'cell_name' => 'B2',
                'cell_class_subject' => 'E2',
                'cell_topic' => 'B3',
            ],
        ],
    ],
];
