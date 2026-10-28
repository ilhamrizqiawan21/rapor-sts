<x-layout.app title="Pengaturan Cetak Rapor STS" userRole="operator">
    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <span>Rapor</span> / <span>Pengaturan Cetak</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert variant="danger">
            <ul style="margin: 0; padding-left: 18px">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <div class="card">
        <div class="ch">
            <div class="ct">Pengaturan Cetak Rapor STS</div>
        </div>
        <form method="POST" action="{{ route('operator.print-settings.update') }}">
            @csrf
            <div class="cb" style="display: flex; flex-direction: column; gap: 18px">
                <div class="f">
                    <label for="jd">Judul Rapor</label>
                    <input id="jd" name="report_title" class="in" value="{{ old('report_title', $setting->report_title) }}" required>
                    <span class="hint">Contoh lain: RAPOR TENGAH SEMESTER, LAPORAN PENILAIAN TENGAH SEMESTER</span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px">
                    <div class="f">
                        <label for="tc">Tempat Cetak</label>
                        <input id="tc" name="print_place" class="in" value="{{ old('print_place', $setting->print_place) }}" required>
                        <span class="hint">Isi dengan nama kabupaten atau kecamatan</span>
                    </div>
                    <div class="f">
                        <label for="tg">Tanggal Cetak</label>
                        <input id="tg" name="print_date" type="date" class="in" value="{{ old('print_date', $setting->print_date?->format('Y-m-d')) }}" required>
                    </div>
                    <div class="f">
                        <label for="kn">Label kolom nilai</label>
                        <input id="kn" name="score_label" class="in" value="{{ old('score_label', $setting->score_label) }}" required>
                    </div>
                    <div class="f">
                        <label for="kt">Ukuran kertas</label>
                        <select id="kt" name="paper_size" class="in" required>
                            <option value="A4" {{ old('paper_size', $setting->paper_size) === 'A4' ? 'selected' : '' }}>A4</option>
                            <option value="F4" {{ old('paper_size', $setting->paper_size) === 'F4' ? 'selected' : '' }}>F4 / Folio</option>
                        </select>
                    </div>
                </div>

                <fieldset style="border: 1px solid #dee2e6; border-radius: 4px; padding: 12px 16px; margin: 0">
                    <legend class="lbl" style="padding: 0 6px; font-weight: 600">Bagian yang ditampilkan di rapor STS</legend>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 4px 16px">
                        <label class="chk">
                            <input type="checkbox" name="sections[show_competency]" value="1" {{ $setting->isSectionVisible('show_competency', true) ? 'checked' : '' }}>
                            Capaian Kompetensi
                        </label>
                        <label class="chk">
                            <input type="checkbox" name="sections[show_attendance]" value="1" {{ $setting->isSectionVisible('show_attendance', true) ? 'checked' : '' }}>
                            Ketidakhadiran
                        </label>
                        <label class="chk">
                            <input type="checkbox" name="sections[show_note]" value="1" {{ $setting->isSectionVisible('show_note', true) ? 'checked' : '' }}>
                            Catatan Wali Kelas
                        </label>
                        <label class="chk">
                            <input type="checkbox" name="sections[show_parent_response]" value="1" {{ $setting->isSectionVisible('show_parent_response', true) ? 'checked' : '' }}>
                            Tanggapan Orang Tua/Wali
                        </label>
                        <label class="chk">
                            <input type="checkbox" name="sections[show_extracurricular]" value="1" {{ $setting->isSectionVisible('show_extracurricular', false) ? 'checked' : '' }}>
                            Ekstrakurikuler
                        </label>
                        <label class="chk">
                            <input type="checkbox" name="sections[show_achievement]" value="1" {{ $setting->isSectionVisible('show_achievement', false) ? 'checked' : '' }}>
                            Prestasi Siswa &amp; Peringkat
                        </label>
                        <label class="chk">
                            <input type="checkbox" name="sections[show_total_score]" value="1" {{ $setting->isSectionVisible('show_total_score', false) ? 'checked' : '' }}>
                            Baris Jumlah nilai
                        </label>
                        <label class="chk">
                            <input type="checkbox" name="sections[show_qr]" value="1" {{ $setting->isSectionVisible('show_qr', false) ? 'checked' : '' }}>
                            QR verifikasi rapor (v1.1)
                        </label>
                    </div>
                </fieldset>

                <div style="display: flex; justify-content: flex-end">
                    <button type="submit" class="btn b-pri">Simpan Pengaturan</button>
                </div>
            </div>
        </form>
    </div>
</x-layout.app>

