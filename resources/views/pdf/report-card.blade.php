<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $printSetting->report_title ?? 'Rapor STS' }}</title>
    <style>
        @page {
            margin: 12mm 14mm 12mm 14mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .page {
            position: relative;
            box-sizing: border-box;
            min-height: 100%;
        }
        .page-break {
            page-break-after: always;
        }
        .watermark {
            position: fixed;
            top: 35%;
            left: 5%;
            width: 90%;
            text-align: center;
            font-size: 72pt;
            color: rgba(180, 180, 180, 0.16);
            font-weight: bold;
            transform: rotate(-35deg);
            z-index: -1000;
        }

        /* Kop */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .kop-logo {
            width: 78px;
            height: 78px;
            vertical-align: middle;
            text-align: center;
        }
        .kop-logo img {
            max-width: 76px;
            max-height: 76px;
        }
        .kop-text {
            text-align: center;
            vertical-align: middle;
        }
        .kop-inst {
            font-weight: bold;
            font-size: 12.5px;
            text-transform: uppercase;
        }
        .kop-name {
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
            font-size: 18px;
            text-transform: uppercase;
            margin: 2px 0;
        }
        .kop-addr {
            font-family: 'Times New Roman', Times, serif;
            font-style: italic;
            font-size: 12px;
        }

        /* Garis Identitas */
        .idt-container {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 4px 0;
            margin-top: 4px;
            margin-bottom: 12px;
        }
        .idt-table {
            width: 100%;
            border-collapse: collapse;
        }
        .idt-table td {
            padding: 2px 3px;
            font-size: 10.5px;
            vertical-align: top;
        }

        /* Judul Rapor */
        .report-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 12px 0 10px;
            text-transform: uppercase;
        }

        /* Tabel Rapor */
        .r-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .r-table th, .r-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: middle;
        }
        .r-table th {
            font-weight: bold;
            text-align: center;
            background-color: #fff;
        }
        .cap {
            font-size: 9px;
            line-height: 1.3;
            text-align: justify;
        }

        /* Footer halaman */
        .page-footer {
            margin-top: 14px;
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .page-footer td {
            padding: 0;
        }

        /* Halaman 2 */
        .sec-title {
            font-weight: bold;
            font-size: 11.5px;
            margin: 12px 0 4px;
        }
        .box {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 9.5px;
            line-height: 1.35;
            min-height: 48px;
            box-sizing: border-box;
        }

        /* Tanda Tangan */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            font-size: 10.5px;
        }
        .sig-table td {
            vertical-align: top;
            padding: 2px 4px;
        }
    </style>
</head>
<body>

@if($watermarkText)
    <div class="watermark">{{ $watermarkText }}</div>
@endif

@foreach($studentsData as $studentIdx => $sData)
    @php
        $student = $sData['student'];
    @endphp

    {{-- HALAMAN 1 --}}
    <div class="page">
        {{-- Kop Madrasah --}}
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    @if($profile->getLogoLeftDataUrl())
                        <img src="{{ $profile->getLogoLeftDataUrl() }}" alt="Logo Madrasah">
                    @endif
                </td>
                <td class="kop-text">
                    <div class="kop-inst">{{ $profile->parent_institution ?? '' }}</div>
                    <div class="kop-name">{{ $profile->name }}</div>
                    @if(! in_array(trim((string) $profile->address), ['', '-'], true))<div class="kop-addr">{{ $profile->address }}</div>@endif
                    <div class="kop-addr">{{ \App\Models\MadrasahProfile::formatRegion($profile->district ?? null, $profile->regency ?? null, $profile->province ?? null) }}</div>
                </td>
                <td class="kop-logo">
                    @if($profile->getLogoRightDataUrl())
                        <img src="{{ $profile->getLogoRightDataUrl() }}" alt="Logo Kanan">
                    @endif
                </td>
            </tr>
        </table>

        {{-- Identitas Siswa & Madrasah --}}
        <div class="idt-container">
            <table class="idt-table">
                <tr>
                    <td style="width: 70px">NAMA</td>
                    <td style="width: 6px">:</td>
                    <td style="width: 320px"><b>{{ $student->name }}</b></td>
                    <td style="width: 90px">Kelas</td>
                    <td style="width: 6px">:</td>
                    <td>{{ $classroom->name }}</td>
                </tr>
                <tr>
                    <td>NIS/NISN</td>
                    <td>:</td>
                    <td>{{ $sData['nis_nisn'] }}</td>
                    <td>Fase</td>
                    <td>:</td>
                    <td>{{ $classroom->phase ?? 'D' }}</td>
                </tr>
                <tr>
                    <td>Madrasah</td>
                    <td>:</td>
                    <td>{{ $profile->name }}</td>
                    <td>Semester</td>
                    <td>:</td>
                    <td>{{ $period->semester->label() }}</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td>{{ $profile->address }}</td>
                    <td>Tahun Pelajaran</td>
                    <td>:</td>
                    <td>{{ $period->academicYear->name }}</td>
                </tr>
            </table>
        </div>

        {{-- Judul Rapor --}}
        <div class="report-title">{{ $printSetting->report_title }}</div>

        {{-- Tabel Nilai --}}
        <table class="r-table">
            <thead>
                <tr>
                    <th style="width: 22px">No</th>
                    <th style="width: 175px">Mata Pelajaran</th>
                    <th style="width: 58px">{{ $printSetting->score_label }}</th>
                    @if($printSetting->isSectionVisible('show_competency', true))
                        <th>Capaian Kompetensi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($sData['subjectRows'] as $sRow)
                    @if($sRow['is_parent'])
                        <tr style="background-color: #f2f2f2">
                            <td style="text-align: center; font-weight: bold">{{ $sRow['number'] }}</td>
                            <td colspan="{{ $printSetting->isSectionVisible('show_competency', true) ? 3 : 2 }}" style="font-weight: bold">
                                {{ $sRow['subject_name'] }}
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td style="text-align: center">{{ $sRow['number'] }}</td>
                            <td style="{{ $sRow['letter'] ? 'padding-left: 14px; font-style: italic;' : '' }}">
                                {{ $sRow['letter'] }} {{ $sRow['subject_name'] }}
                            </td>
                            <td style="text-align: center; font-weight: bold">
                                {{ $sRow['formatted_score'] }}
                            </td>
                            @if($printSetting->isSectionVisible('show_competency', true))
                                <td class="cap">{{ $sRow['description'] }}</td>
                            @endif
                        </tr>
                    @endif
                @endforeach

                @if($printSetting->isSectionVisible('show_total_score', false) && $sData['totalScore'] !== null)
                    <tr style="font-weight: bold; background-color: #f8f9fa">
                        <td colspan="2" style="text-align: center">Jumlah Nilai</td>
                        <td style="text-align: center">{{ $sData['totalScore'] }}</td>
                        @if($printSetting->isSectionVisible('show_competency', true))
                            <td>Rata-rata: {{ $sData['formattedAverage'] }}</td>
                        @endif
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Footer Halaman 1 --}}
        <table class="page-footer">
            <tr>
                <td style="text-align: left">{{ $classroom->name }} _ {{ $student->name }} _ {{ $student->nisn }}</td>
                <td style="text-align: right">Halaman 1</td>
            </tr>
        </table>
    </div>

    <div class="page-break"></div>

    {{-- HALAMAN 2 --}}
    <div class="page">
        {{-- Ketidakhadiran --}}
        @if($printSetting->isSectionVisible('show_attendance', true))
            <div class="sec-title" style="margin-top: 0">Ketidakhadiran</div>
            <table class="r-table" style="width: 100%; max-width: 420px">
                <tbody>
                    <tr>
                        <td style="width: 180px; font-weight: bold">Sakit</td>
                        <td style="width: 70px; text-align: center">{{ $sData['sick'] }}</td>
                        <td>Hari</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold">Izin</td>
                        <td style="text-align: center">{{ $sData['permit'] }}</td>
                        <td>Hari</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold">Alpa</td>
                        <td style="text-align: center">{{ $sData['absent'] }}</td>
                        <td>Hari</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Catatan Wali Kelas --}}
        @if($printSetting->isSectionVisible('show_note', true))
            <div class="sec-title">Catatan Wali Kelas</div>
            <div class="box">
                {{ $sData['note'] ?: '–' }}
            </div>
        @endif

        {{-- Tanggapan Orang Tua/Wali --}}
        @if($printSetting->isSectionVisible('show_parent_response', true))
            <div class="sec-title">Tanggapan Orang Tua/Wali</div>
            <div class="box" style="height: 52px"></div>
        @endif

        @if($printSetting->isSectionVisible('show_extracurricular', false))
            <div class="sec-title">Ekstrakurikuler</div>
            <table class="r-table">
                <thead>
                    <tr>
                        <th style="width: 24px">No</th>
                        <th>Kegiatan</th>
                        <th style="width: 95px">Predikat</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sData['extracurriculars'] as $extraIndex => $extra)
                        <tr>
                            <td style="text-align: center">{{ $extraIndex + 1 }}</td>
                            <td>{{ $extra->name }}</td>
                            <td style="text-align: center">{{ $extra->predicate }}</td>
                            <td>{{ $extra->description ?: '–' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center">–</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if($printSetting->isSectionVisible('show_achievement', false))
            <div class="sec-title">Prestasi Akademik</div>
            <div class="box">
                Peringkat kelas:
                <b>{{ $sData['rank'] ? $sData['rank'].' dari '.$sData['rank_total'].' siswa' : '–' }}</b>
            </div>
        @endif

        {{-- Tanda Tangan --}}
        <table class="sig-table">
            <tr>
                <td style="width: 48%; padding-left: 20px">
                    Orang Tua/Wali
                </td>
                <td style="width: 52%; padding-left: 60px">
                    {{ $printSetting->print_place }}, {{ $formattedDate }}<br>
                    Wali Kelas
                </td>
            </tr>
            <tr>
                <td style="height: 50px"></td>
                <td style="height: 50px"></td>
            </tr>
            <tr>
                <td style="padding-left: 20px">
                    <div style="width: 140px; border-bottom: 1px solid #000"></div>
                </td>
                <td style="padding-left: 60px">
                    <b>{{ $classroom->homeroomTeacher?->name ?? '[Nama Wali Kelas]' }}</b><br>
                    NIP. {{ $classroom->homeroomTeacher?->nip ?? '–' }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; padding-top: 24px">
                    Mengetahui<br>
                    Kepala Madrasah
                    <div style="height: 50px"></div>
                    <b>{{ $profile->headmaster_name ?? '[Nama Kepala Madrasah]' }}</b><br>
                    NIP. {{ $profile->headmaster_nip ?? '–' }}
                </td>
            </tr>
        </table>

        {{-- Footer Halaman 2 --}}
        <table class="page-footer" style="margin-top: 30px">
            <tr>
                <td style="text-align: left">{{ $classroom->name }} _ {{ $student->name }} _ {{ $student->nisn }}</td>
                <td style="text-align: right">Halaman 2</td>
            </tr>
        </table>
    </div>

    @if(! $loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>

