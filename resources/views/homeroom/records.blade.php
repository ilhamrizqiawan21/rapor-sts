<x-layout.app title="Wali Kelas · Kehadiran dan Catatan" userRole="guru">
    <x-slot:breadcrumb>
        <a href="{{ route('guru.dashboard') }}">Home</a> / <span>Wali Kelas</span> / <span>Kehadiran &amp; Catatan</span>
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

    @if($isLocked)
        <div class="alert" style="background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; margin-bottom: 16px">
            <b>Perhatian:</b> Nilai dan data kelas {{ $classroom->name }} telah <b>dikunci</b>. Kehadiran dan catatan tidak dapat diubah lagi kecuali kunci dibuka.
        </div>
    @endif

    <div class="card">
        <div class="ch">
            <div>
                <div class="ct">Kehadiran &amp; Catatan Wali Kelas</div>
                <div class="cs">{{ $classroom->name }} · {{ count($students) }} siswa · periode awal semester s.d. tengah semester</div>
            </div>
            <div class="acts grp">
                <a href="{{ route('wali.kehadiran.template') }}" class="btn b-inf">Download Template</a>
                @if(! $isLocked)
                    <button type="button" class="btn b-suc" onclick="document.getElementById('modal-upload').style.display='flex'">Upload Excel</button>
                    <button type="button" class="btn b-inf" onclick="document.getElementById('modal-massal').style.display='flex'">Isi catatan massal</button>
                    <button type="button" class="btn b-pri" onclick="document.getElementById('form-records').submit()">Simpan</button>
                @endif
            </div>
        </div>

        <div class="cb" style="display: flex; flex-direction: column; gap: 14px">
            <div class="alert a-lt">
                Catatan massal mengisi catatan yang sama untuk siswa yang masih kosong. Catatan yang sudah diketik per siswa tidak ditimpa.
            </div>

            <form id="form-records" method="POST" action="{{ route('wali.kehadiran.update') }}">
                @csrf
                <div class="tb">
                    <table class="t">
                        <thead>
                            <tr>
                                <th style="width: 36px">#</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th style="text-align: center; width: 80px">Sakit</th>
                                <th style="text-align: center; width: 80px">Izin</th>
                                <th style="text-align: center; width: 80px">Alpa</th>
                                <th style="min-width: 380px">Catatan Wali Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $idx => $student)
                                @php
                                    $rec = $records->get($student->id);
                                    $sick = old("records.{$idx}.sick", $rec ? $rec->sick : 0);
                                    $permit = old("records.{$idx}.permit", $rec ? $rec->permit : 0);
                                    $absent = old("records.{$idx}.absent", $rec ? $rec->absent : 0);
                                    $note = old("records.{$idx}.note", $rec?->note ?? '');
                                @endphp
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <input type="hidden" name="records[{{ $idx }}][student_id]" value="{{ $student->id }}">
                                        {{ $student->nisn }}
                                    </td>
                                    <td><b>{{ $student->name }}</b></td>
                                    <td style="text-align: center">
                                        <input class="in num" type="number" min="0" name="records[{{ $idx }}][sick]" value="{{ $sick }}" aria-label="Sakit {{ $student->name }}" {{ $isLocked ? 'disabled' : '' }}>
                                    </td>
                                    <td style="text-align: center">
                                        <input class="in num" type="number" min="0" name="records[{{ $idx }}][permit]" value="{{ $permit }}" aria-label="Izin {{ $student->name }}" {{ $isLocked ? 'disabled' : '' }}>
                                    </td>
                                    <td style="text-align: center">
                                        <input class="in num" type="number" min="0" name="records[{{ $idx }}][absent]" value="{{ $absent }}" aria-label="Alpa {{ $student->name }}" {{ $isLocked ? 'disabled' : '' }}>
                                    </td>
                                    <td>
                                        <textarea class="in" name="records[{{ $idx }}][note]" maxlength="500" placeholder="Catatan untuk siswa (maks 500 karakter)" aria-label="Catatan {{ $student->name }}" {{ $isLocked ? 'disabled' : '' }}>{{ $note }}</textarea>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 24px; color: #6c757d">
                                        Belum ada siswa aktif terdaftar di kelas {{ $classroom->name }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(! $isLocked && count($students) > 0)
                    <div style="margin-top: 16px; display: flex; justify-content: flex-end">
                        <button type="submit" class="btn b-pri">Simpan Perubahan</button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Modal Isi Catatan Massal --}}
    @if(! $isLocked)
        <div id="modal-massal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 16px">
            <div class="card" style="width: 100%; max-width: 520px">
                <div class="ch">
                    <div class="ct">Isi Catatan Massal</div>
                    <button type="button" style="margin-left: auto; background: none; border: 0; font-size: 18px; cursor: pointer" onclick="document.getElementById('modal-massal').style.display='none'">&times;</button>
                </div>
                <form method="POST" action="{{ route('wali.kehadiran.batch_note') }}">
                    @csrf
                    <div class="cb" style="display: flex; flex-direction: column; gap: 12px">
                        <div class="alert a-lt" style="font-size: 12px">
                            Catatan ini hanya akan diisi ke siswa yang catatannya saat ini masih kosong. Catatan yang sudah ada tidak akan terhapus/tertimpa.
                        </div>
                        <div class="f">
                            <label for="massal-note">Teks Catatan (maksimal 500 karakter)</label>
                            <textarea id="massal-note" name="note" class="in" style="height: 90px" maxlength="500" required placeholder="Contoh: Pertahankan prestasi belajarmu dan terus tingkatkan kedisiplinan beribadah."></textarea>
                        </div>
                    </div>
                    <div style="padding: 12px 16px; background: #f8f9fa; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 8px">
                        <button type="button" class="btn" style="background: #6c757d" onclick="document.getElementById('modal-massal').style.display='none'">Batal</button>
                        <button type="submit" class="btn b-pri">Terapkan ke Siswa Kosong</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Upload Excel --}}
        <div id="modal-upload" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 16px">
            <div class="card" style="width: 100%; max-width: 480px">
                <div class="ch">
                    <div class="ct">Upload Excel Kehadiran &amp; Catatan</div>
                    <button type="button" style="margin-left: auto; background: none; border: 0; font-size: 18px; cursor: pointer" onclick="document.getElementById('modal-upload').style.display='none'">&times;</button>
                </div>
                <form method="POST" action="{{ route('wali.kehadiran.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="cb" style="display: flex; flex-direction: column; gap: 12px">
                        <div class="alert a-lt" style="font-size: 12px">
                            Gunakan template Excel yang diunduh dari tombol "Download Template". Format file harus .xlsx (maks 2 MB).
                        </div>
                        <div class="f">
                            <label for="excel-file">Pilih File Excel (.xlsx)</label>
                            <input id="excel-file" type="file" name="file" accept=".xlsx" class="in" required>
                        </div>
                    </div>
                    <div style="padding: 12px 16px; background: #f8f9fa; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 8px">
                        <button type="button" class="btn" style="background: #6c757d" onclick="document.getElementById('modal-upload').style.display='none'">Batal</button>
                        <button type="submit" class="btn b-suc">Unggah Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-layout.app>
