<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Leger Nilai STS — {{ $classroom->name }}</title>
    <style>
        @page {
            margin: 8mm 10mm 10mm 10mm;
            size: landscape;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }
        .kop-logo {
            width: 65px;
            vertical-align: middle;
            text-align: left;
        }
        .kop-logo img {
            max-width: 60px;
            max-height: 60px;
        }
        .kop-text {
            text-align: center;
            vertical-align: middle;
        }
        .kop-inst {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .kop-name {
            font-weight: bold;
            font-size: 15px;
            text-transform: uppercase;
            margin: 1px 0;
        }
        .kop-addr {
            font-size: 8.5px;
        }
        .divider {
            border-top: 2px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin: 4px 0 6px 0;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 6px;
        }
        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .section-title {
            font-weight: bold;
            font-size: 10.5px;
            margin-bottom: 2px;
        }
        .kktp-subtitle {
            font-size: 8.5px;
            margin-bottom: 6px;
        }
        .t-ledger {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .t-ledger th, .t-ledger td {
            border: 1px solid #000;
            padding: 2.5px 1.5px;
            text-align: center;
            vertical-align: middle;
        }
        .t-ledger th {
            font-weight: bold;
            background-color: #f1f3f5;
        }
        .t-ledger td.name {
            text-align: left;
            padding-left: 5px;
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
            margin-top: 14px;
            font-size: 9.5px;
            page-break-inside: avoid;
        }
        .sig-table td {
            vertical-align: top;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

{{-- Skrip nomor halaman running footer --}}
<script type="text/php">
if (isset($pdf)) {
    $text = "Halaman {PAGE_NUM}";
    $size = 8;
    $font = $fontMetrics->getFont("Arial, Helvetica, sans-serif", "normal");
    $pdf->page_text(750, 570, $text, $font, $size, array(0,0,0));
    $pdf->page_text(28, 570, "LEGGER KELAS {{ $classroom->name }}", $font, $size, array(0,0,0));
}
</script>

{{-- ======================================================== --}}
{{-- BAGIAN A: PENGETAHUAN                                     --}}
{{-- ======================================================== --}}
<div class="section-container">
    {{-- Kop Madrasah --}}
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if($profile->getLogoLeftDataUrl())
                    <img src="{{ $profile->getLogoLeftDataUrl() }}" alt="Logo">
                @endif
            </td>
            <td class="kop-text">
                <div class="kop-inst">{{ $profile->parent_institution ?? 'KEMENTERIAN AGAMA REPUBLIK INDONESIA' }}</div>
                <div class="kop-name">{{ $profile->name }}</div>
                <div class="kop-addr">{{ $profile->address }}</div>
                <div class="kop-addr">Kecamatan {{ $profile->district ?? '–' }}, Kabupaten {{ $profile->regency ?? '–' }} - {{ $profile->province ?? '–' }}</div>
            </td>
            <td class="kop-logo" style="text-align: right">
                @if($profile->getLogoRightDataUrl())
                    <img src="{{ $profile->getLogoRightDataUrl() }}" alt="Logo Kanan">
                @endif
            </td>
        </tr>
    </table>
    <div class="divider"></div>

    {{-- Metadata 2 Kolom --}}
    <table class="meta-table">
        <tr>
            <td style="width: 50%">
                <b>Kelas :</b> {{ $classroom->name }}<br>
                <b>Madrasah :</b> {{ $profile->name }}
            </td>
            <td style="width: 50%; text-align: right">
                <b>Tahun Pelajaran :</b> {{ $period->academicYear->name }}<br>
                <b>Semester :</b> {{ $period->semester->label() }}
            </td>
        </tr>
    </table>

    <div class="title">LEGGER KELAS</div>
    <div class="section-title">A. PENGETAHUAN</div>
    <div class="kktp-subtitle">Kriteria Ketuntasan Minimal = {{ (int) $defaultKktp }} (x)</div>

    {{-- Tabel Leger Pengetahuan --}}
    <table class="t-ledger">
        <thead>
            <tr>
                <th rowspan="2" style="width: 22px">No</th>
                <th rowspan="2" style="text-align: left; padding-left: 5px">Nama</th>
                @foreach($columnGroups as $cg)
                    @if(! empty($cg['is_group']))
                        <th colspan="{{ count($cg['columns'] ?? []) }}">{{ $cg['code'] }}</th>
                    @else
                        <th rowspan="2">{{ $cg['code'] }}</th>
                    @endif
                @endforeach
                <th rowspan="2" style="width: 42px">TOTAL</th>
            </tr>
            <tr>
                @foreach($columnGroups as $cg)
                    @if(! empty($cg['is_group']))
                        @foreach($cg['columns'] as $sc)
                            <th>{{ $sc['code'] }}</th>
                        @endforeach
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rowsPengetahuan as $idx => $r)
                @php $student = $r['student']; @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
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
                </tr>
            @endforeach

            {{-- Baris Rata-rata Kelas --}}
            <tr class="avg-row">
                <td colspan="2" style="text-align: center">Rata-rata Kelas</td>
                @foreach($subjectColumns as $col)
                    <td>{{ $classAveragesPengetahuan[$col['id']] ?? '–' }}</td>
                @endforeach
                <td>{{ $overallAveragePengetahuan }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Tanda Tangan Pengetahuan --}}
    <table class="sig-table">
        <tr>
            <td style="width: 50%; text-align: left">
                Mengetahui<br>
                Kepala Madrasah
                <div style="height: 48px"></div>
                <b>{{ $profile->headmaster_name ?? '[Nama Kepala Madrasah]' }}</b><br>
                NIP. {{ $profile->headmaster_nip ?? '–' }}
            </td>
            <td style="width: 50%; text-align: right">
                {{ $printSetting->print_place ?? $profile->regency }}, {{ $formattedDate }}<br>
                Wali Kelas:
                <div style="height: 48px"></div>
                <b>{{ $classroom->homeroomTeacher?->name ?? '–' }}</b><br>
                NIP. {{ $classroom->homeroomTeacher?->nip ?? '–' }}
            </td>
        </tr>
    </table>
</div>

{{-- ======================================================== --}}
{{-- BAGIAN B: KETERAMPILAN                                   --}}
{{-- ======================================================== --}}
<div class="page-break"></div>

<div class="section-container">
    {{-- Metadata 2 Kolom --}}
    <table class="meta-table" style="margin-top: 4px">
        <tr>
            <td style="width: 50%">
                <b>Kelas :</b> {{ $classroom->name }}<br>
                <b>Madrasah :</b> {{ $profile->name }}
            </td>
            <td style="width: 50%; text-align: right">
                <b>Tahun Pelajaran :</b> {{ $period->academicYear->name }}<br>
                <b>Semester :</b> {{ $period->semester->label() }}
            </td>
        </tr>
    </table>

    <div class="section-title">B. KETERAMPILAN</div>
    <div class="kktp-subtitle">Kriteria Ketuntasan Minimal = {{ (int) $defaultKktp }} (x)</div>

    {{-- Tabel Leger Keterampilan --}}
    <table class="t-ledger">
        <thead>
            <tr>
                <th rowspan="2" style="width: 22px">No</th>
                <th rowspan="2" style="text-align: left; padding-left: 5px">Nama</th>
                @foreach($columnGroups as $cg)
                    @if(! empty($cg['is_group']))
                        <th colspan="{{ count($cg['columns'] ?? []) }}">{{ $cg['code'] }}</th>
                    @else
                        <th rowspan="2">{{ $cg['code'] }}</th>
                    @endif
                @endforeach
                <th rowspan="2" style="width: 42px">TOTAL</th>
            </tr>
            <tr>
                @foreach($columnGroups as $cg)
                    @if(! empty($cg['is_group']))
                        @foreach($cg['columns'] as $sc)
                            <th>{{ $sc['code'] }}</th>
                        @endforeach
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rowsKeterampilan as $idx => $r)
                @php $student = $r['student']; @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
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
                </tr>
            @endforeach

            {{-- Baris Rata-rata Kelas --}}
            <tr class="avg-row">
                <td colspan="2" style="text-align: center">Rata-rata Kelas</td>
                @foreach($subjectColumns as $col)
                    <td>{{ $classAveragesKeterampilan[$col['id']] ?? '–' }}</td>
                @endforeach
                <td>{{ $overallAverageKeterampilan }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Tanda Tangan Keterampilan --}}
    <table class="sig-table">
        <tr>
            <td style="width: 50%; text-align: left">
                Mengetahui<br>
                Kepala Madrasah
                <div style="height: 48px"></div>
                <b>{{ $profile->headmaster_name ?? '[Nama Kepala Madrasah]' }}</b><br>
                NIP. {{ $profile->headmaster_nip ?? '–' }}
            </td>
            <td style="width: 50%; text-align: right">
                {{ $printSetting->print_place ?? $profile->regency }}, {{ $formattedDate }}<br>
                Wali Kelas:
                <div style="height: 48px"></div>
                <b>{{ $classroom->homeroomTeacher?->name ?? '–' }}</b><br>
                NIP. {{ $classroom->homeroomTeacher?->nip ?? '–' }}
            </td>
        </tr>
    </table>
</div>

</body>
</html>
