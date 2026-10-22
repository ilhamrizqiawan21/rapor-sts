<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Pemantauan STS — {{ $profile->name }}</title>
    <style>
        @page {
            margin: 12mm 14mm 12mm 14mm;
            size: landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .inst {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 2px 0;
        }
        .sub {
            font-size: 10.5px;
        }
        .t-recap {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            margin-bottom: 16px;
        }
        .t-recap th, .t-recap td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }
        .t-recap th {
            font-weight: bold;
            background-color: #f1f3f5;
        }
        .t-recap td.left {
            text-align: left;
            padding-left: 8px;
        }
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 10.5px;
        }
        .sig-table td {
            vertical-align: top;
            padding: 0 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="inst">{{ $profile->parent_institution ?? '' }}</div>
    <div class="title">{{ $profile->name }}</div>
    <div class="sub">
        LAPORAN REKAPITULASI PROGRES & NILAI SUMATIF TENGAH SEMESTER<br>
        Semester: {{ $period->semester->label() }} | Tahun Pelajaran: {{ $period->academicYear->name }}
    </div>
</div>

<table class="t-recap">
    <thead>
        <tr>
            <th style="width: 30px">No</th>
            <th>Kelas</th>
            <th style="text-align: left; padding-left: 8px">Wali Kelas</th>
            <th style="width: 110px">Mapel Terkirim</th>
            <th style="width: 90px">Status Kunci</th>
            <th style="width: 90px">Rerata Kelas</th>
            <th style="width: 130px">Siswa Belum Tercapai</th>
        </tr>
    </thead>
    <tbody>
        @foreach($classProgress as $idx => $cp)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td><b>{{ $cp['classroom']->name }}</b></td>
                <td class="left">{{ $cp['homeroom_teacher'] }}</td>
                <td>{{ $cp['submitted_count'] }}/{{ $cp['total_count'] }} ({{ $cp['progress_percent'] }}%)</td>
                <td>{{ $cp['is_locked'] ? 'Dikunci' : 'Belum Dikunci' }}</td>
                <td style="font-weight: bold">{{ $cp['class_average'] }}</td>
                <td>{{ $cp['students_below_kktp_count'] > 0 ? $cp['students_below_kktp_count'].' siswa' : '0 siswa' }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr style="font-weight: bold; background-color: #f8f9fa">
            <td colspan="3" style="text-align: center">Rata-rata Seluruh Madrasah</td>
            <td>{{ $submittedCount }}/{{ $totalAssignments }} ({{ $percentSubmitted }}%)</td>
            <td>{{ $lockedClassroomsCount }}/{{ $totalClassrooms }} Kelas</td>
            <td>{{ $overallAverage }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<table class="sig-table">
    <tr>
        <td style="width: 60%"></td>
        <td style="width: 40%; text-align: center">
            {{ $printSetting->print_place ?? '-' }}, {{ Carbon\Carbon::parse($printSetting->print_date)->translatedFormat('d F Y') }}<br>
            Kepala Madrasah
            <div style="height: 50px"></div>
            <b>{{ $profile->headmaster_name ?? '[Nama Kepala Madrasah]' }}</b><br>
            NIP. {{ $profile->headmaster_nip ?? '–' }}
        </td>
    </tr>
</table>

</body>
</html>
