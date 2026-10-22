<x-layout.app title="Bobot Nilai — {{ $teachingAssignment->subject->name }} {{ $teachingAssignment->classroom->name }}" userRole="guru">
    <x-slot:topbarLeft>
        <a class="tl" href="{{ route('guru.dashboard') }}">Beranda</a>
        <a class="tl on" href="{{ route('guru.bobot.edit', $teachingAssignment) }}">Bobot</a>
        <a class="tl" href="{{ route('guru.penilaian.index', $teachingAssignment) }}">Penilaian</a>
    </x-slot:topbarLeft>

    <x-slot:breadcrumb>
        <a href="{{ route('guru.dashboard') }}">Home</a> / <a href="#">Guru</a> / <span>Bobot</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert variant="warning">{{ session('error') }}</x-alert>
    @endif
    @if($errors->any())
        <x-alert variant="warning">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </x-alert>
    @endif

    @php
        $isLocked = $teachingAssignment->isSubmitted() || $teachingAssignment->classroom->isLockedInPeriod($teachingAssignment->period_id);
        $wS = (float) $teachingAssignment->weight_summative;
        $wT = (float) $teachingAssignment->weight_sts;
        $totalWeight = $wS + $wT;
        $pctS = $totalWeight > 0 ? round(($wS / $totalWeight) * 100, 1) : 0;
        $pctT = $totalWeight > 0 ? round(($wT / $totalWeight) * 100, 1) : 0;
    @endphp

    @if($isLocked)
        <x-alert variant="warning">
            Penilaian untuk mapel ini telah dikirim atau kelas telah dikunci. Pengaturan bobot tidak dapat diubah lagi.
        </x-alert>
    @endif

    <div class="card">
        <div class="ch">
            <div class="ct">Bobot · {{ $teachingAssignment->subject->name }} {{ $teachingAssignment->classroom->name }}</div>
        </div>
        <div class="cb" style="display: flex; flex-direction: column; gap: 20px">
            <form method="POST" action="{{ route('guru.bobot.update', $teachingAssignment) }}" id="formBobot">
                @csrf
                @method('PUT')

                <div class="lab" style="max-width: 420px; margin-bottom: 20px">
                    <label for="jj" style="font-weight: 600">Jumlah Jam Perminggu:</label>
                    <input id="jj" name="hours_per_week" class="in" type="number" min="0" value="{{ old('hours_per_week', $teachingAssignment->hours_per_week) }}" style="max-width: 120px" @disabled($isLocked) required>
                </div>

                <div style="border: 1px solid #dee2e6; border-radius: 4px; overflow: hidden; margin-bottom: 20px">
                    <div style="padding: 12px 16px; border-bottom: 1px solid #dee2e6; font-weight: 700; background: #f8f9fa">
                        Bobot Nilai Rapor STS
                    </div>
                    <div style="padding: 16px; display: flex; flex-direction: column; gap: 18px">
                        <div class="alert a-lav" style="margin: 0">
                            Nilai Rapor STS = (Rerata Sumatif Lingkup Materi × bobot Sumatif) + (Nilai STS × bobot STS). Jika madrasah tidak menyelenggarakan tes STS, isi bobot STS dengan 0. Total bobot tidak harus 100.
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; align-items: start">
                            <div style="display: flex; flex-direction: column; gap: 6px">
                                <div class="lab">
                                    <label for="bs" style="font-weight: 600">Sumatif LM:</label>
                                    <input id="bs" name="weight_summative" class="in" type="number" step="0.01" min="0" value="{{ old('weight_summative', $wS) }}" oninput="recalcPercentage()" @disabled($isLocked) required>
                                </div>
                                <span class="hint" style="padding-left: 162px">Bobot: <b id="hintSummative">{{ $pctS }}%</b></span>
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 6px">
                                <div class="lab">
                                    <label for="bt" style="font-weight: 600">STS:</label>
                                    <input id="bt" name="weight_sts" class="in" type="number" step="0.01" min="0" value="{{ old('weight_sts', $wT) }}" oninput="recalcPercentage()" @disabled($isLocked) required>
                                </div>
                                <span class="hint" style="padding-left: 162px">Bobot: <b id="hintSts">{{ $pctT }}%</b></span>
                            </div>
                        </div>
                    </div>
                </div>

                @if(! $isLocked)
                    <div style="display: flex; justify-content: flex-end">
                        <button type="submit" class="btn b-pri">Simpan</button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script>
        function recalcPercentage() {
            const ws = parseFloat(document.getElementById('bs').value) || 0;
            const wt = parseFloat(document.getElementById('bt').value) || 0;
            const total = ws + wt;

            if (total > 0) {
                const pctS = ((ws / total) * 100).toFixed(1);
                const pctT = ((wt / total) * 100).toFixed(1);
                document.getElementById('hintSummative').innerText = pctS + '%';
                document.getElementById('hintSts').innerText = pctT + '%';
            } else {
                document.getElementById('hintSummative').innerText = '0%';
                document.getElementById('hintSts').innerText = '0%';
            }
        }
    </script>
</x-layout.app>

