<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Leger Nilai STS — {{ $classroom->name }}</title>
    <style>
        @page {
            margin: 10mm 12mm 10mm 12mm;
            size: landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 11px;
            margin-top: 2px;
        }
        .t-ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .t-ledger th, .t-ledger td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
        }
        .t-ledger th {
            font-weight: bold;
            background-color: #f1f3f5;
        }
        .t-ledger td.name {
            text-align: left;
            padding-left: 6px;
            white-space: nowrap;
        }
        .below-kktp {
            font-weight: bold;
            text-decoration: underline;
        }
        .avg-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
    <div class="title">LEGER NILAI SUMATIF TENGAH SEMESTER</div>
    <div class="subtitle">
        {{ $profile->name }} | Kelas: {{ $classroom->name }} | Semester: {{ $period->semester->label() }} | Tahun Pelajaran: {{ $period->academicYear->name }}
    </div>
</div>

<table class="t-ledger">
    <thead>
        <tr>
            <th style="width: 25px">No</th>
            <th style="width: 70px">NISN</th>
            <th style="text-align: left; padding-left: 6px">Nama Siswa</th>
            @foreach($subjectColumns as $col)
                <th title="{{ $col['name'] }} (KKTP: {{ $col['kktp'] }})">
                    {{ $col['code'] }}
                </th>
            @endforeach
            <th style="width: 45px">Jumlah</th>
            <th style="width: 45px">Rerata</th>
            <th style="width: 35px">Rank</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $idx => $r)
            @php $student = $r['student']; @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $student->nisn }}</td>
                <td class="name">{{ $student->name }}</td>
                @foreach($subjectColumns as $col)
                    @php
                        $sc = $r['scores'][$col['id']] ?? null;
                    @endphp
                    <td class="{{ ($sc['is_below'] ?? false) ? 'below-kktp' : '' }}">
                        {{ $sc ? $sc['formatted'] : '–' }}
                    </td>
                @endforeach
                <td style="font-weight: bold">{{ $r['total'] }}</td>
                <td style="font-weight: bold">{{ $r['formatted_average'] }}</td>
                <td style="font-weight: bold">{{ $r['rank'] }}</td>
            </tr>
        @endforeach

        {{-- Baris Rata-rata Kelas --}}
        <tr class="avg-row">
            <td colspan="3" style="text-align: center">Rata-rata Kelas</td>
            @foreach($subjectColumns as $col)
                <td>{{ $classAverages[$col['id']] ?? '–' }}</td>
            @endforeach
            <td></td>
            <td>{{ $overallAverage }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

<table class="sig-table">
    <tr>
        <td style="width: 50%; text-align: left">
            Mengetahui,<br>
            Kepala Madrasah
            <div style="height: 48px"></div>
            <b>{{ $profile->headmaster_name ?? '[Nama Kepala Madrasah]' }}</b><br>
            NIP. {{ $profile->headmaster_nip ?? '–' }}
        </td>
        <td style="width: 50%; text-align: right">
            {{ $printSetting->print_place }}, {{ Carbon\Carbon::parse($printSetting->print_date)->translatedFormat('d F Y') }}<br>
            Wali Kelas {{ $classroom->name }}
            <div style="height: 48px"></div>
            <b>{{ $classroom->homeroomTeacher?->name ?? '[Nama Wali Kelas]' }}</b><br>
            NIP. {{ $classroom->homeroomTeacher?->nip ?? '–' }}
        </td>
    </tr>
</table>

</body>
</html>
