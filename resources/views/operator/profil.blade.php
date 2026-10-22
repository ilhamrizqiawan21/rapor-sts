<x-layout.app title="Profil Madrasah dan Branding" userRole="operator">
    <x-slot:sidebar>
        <x-sidebar.operator active="profil" />
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route('operator.dashboard') }}">Home</a> / <a href="#">Admin</a> / <span>Profil Madrasah</span>
    </x-slot:breadcrumb>

    @if(session('success'))
        <x-alert variant="info">{{ session('success') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert variant="warning">
            <ul style="margin: 0; padding-left: 20px">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ route('operator.profil.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="card">
            <div class="ch">
                <div>
                    <div class="ct">Profil Madrasah</div>
                    <div class="cs">Identitas ini tampil di kop rapor, halaman login, dan header aplikasi</div>
                </div>
                <div class="acts">
                    <button type="submit" class="btn b-pri">Simpan</button>
                </div>
            </div>

            <div class="cb" style="display: flex; flex-direction: column; gap: 28px">
                {{-- Seksi 1: Identitas Lembaga --}}
                <section>
                    <div style="font-size: 14px; font-weight: 700; margin: 0 0 12px; color: var(--sidebar); text-transform: uppercase; letter-spacing: .03em">
                        Identitas Lembaga
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px">
                        <div class="f">
                            <label for="parent_institution">Instansi Induk (baris 1 kop)</label>
                            <input id="parent_institution" name="parent_institution" class="in" value="{{ old('parent_institution', $profile->parent_institution) }}">
                            <span class="hint">Kosongkan jika kop tidak memakai baris instansi induk</span>
                        </div>
                        <div class="f">
                            <label for="foundation_name">Yayasan / Penyelenggara (opsional)</label>
                            <input id="foundation_name" name="foundation_name" class="in" placeholder="Nama yayasan" value="{{ old('foundation_name', $profile->foundation_name) }}">
                        </div>
                        <div class="f">
                            <label for="name">Nama Madrasah</label>
                            <input id="name" name="name" class="in" value="{{ old('name', $profile->name) }}" required>
                        </div>
                        <div class="f">
                            <label for="level">Jenjang</label>
                            <select id="level" name="level" class="in" required>
                                <option value="MTs" @selected(old('level', $profile->level) === 'MTs')>MTs</option>
                                <option value="MI" @selected(old('level', $profile->level) === 'MI')>MI</option>
                                <option value="MA" @selected(old('level', $profile->level) === 'MA')>MA</option>
                            </select>
                        </div>
                        <div class="f">
                            <label for="nsm">NSM</label>
                            <input id="nsm" name="nsm" class="in" value="{{ old('nsm', $profile->nsm) }}" required>
                        </div>
                        <div class="f">
                            <label for="npsn">NPSN</label>
                            <input id="npsn" name="npsn" class="in" value="{{ old('npsn', $profile->npsn) }}" required>
                        </div>
                        <div class="f" style="grid-column: 1 / -1">
                            <label for="address">Alamat</label>
                            <input id="address" name="address" class="in" value="{{ old('address', $profile->address) }}" required>
                        </div>
                        <div class="f">
                            <label for="district">Kecamatan</label>
                            <input id="district" name="district" class="in" value="{{ old('district', $profile->district) }}" required>
                        </div>
                        <div class="f">
                            <label for="regency">Kabupaten/Kota</label>
                            <input id="regency" name="regency" class="in" value="{{ old('regency', $profile->regency) }}" required>
                        </div>
                        <div class="f">
                            <label for="province">Provinsi</label>
                            <input id="province" name="province" class="in" value="{{ old('province', $profile->province) }}" required>
                        </div>
                        <div class="f">
                            <label for="contact">Telepon / Email (opsional)</label>
                            <input id="contact" name="contact" class="in" value="{{ old('contact', $profile->contact) }}">
                        </div>
                    </div>
                </section>

                {{-- Seksi 2: Kepala Madrasah --}}
                <section>
                    <div style="font-size: 14px; font-weight: 700; margin: 0 0 12px; color: var(--sidebar); text-transform: uppercase; letter-spacing: .03em">
                        Kepala Madrasah
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px">
                        <div class="f">
                            <label for="headmaster_name">Nama Kepala Madrasah</label>
                            <input id="headmaster_name" name="headmaster_name" class="in" value="{{ old('headmaster_name', $profile->headmaster_name) }}" required>
                        </div>
                        <div class="f">
                            <label for="headmaster_nip">NIP Kepala Madrasah</label>
                            <input id="headmaster_nip" name="headmaster_nip" class="in" placeholder="Kosongkan jika tidak ada" value="{{ old('headmaster_nip', $profile->headmaster_nip) }}">
                        </div>
                    </div>
                </section>

                {{-- Seksi 3: Logo Kop Rapor --}}
                <section>
                    <div style="font-size: 14px; font-weight: 700; margin: 0 0 12px; color: var(--sidebar); text-transform: uppercase; letter-spacing: .03em">
                        Logo Kop Rapor
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px">
                        <div class="f">
                            <label for="logo_left">Logo Kiri (Wajib untuk cetak)</label>
                            <input id="logo_left" name="logo_left" type="file" accept="image/*" class="in" style="padding: 4px 10px">
                            <span class="hint">Disarankan persegi, min. 300×300 px (PNG/JPG, maks 1MB)</span>
                            @if($profile->logo_left_path)
                                <div style="margin-top: 6px">
                                    <img src="{{ asset('storage/' . $profile->logo_left_path) }}" alt="Logo Kiri" style="height: 60px; object-fit: contain">
                                </div>
                            @endif
                        </div>
                        <div class="f">
                            <label for="logo_right">Logo Kanan (Opsional)</label>
                            <input id="logo_right" name="logo_right" type="file" accept="image/*" class="in" style="padding: 4px 10px">
                            <span class="hint">Kosongkan jika kop hanya memakai satu logo</span>
                            @if($profile->logo_right_path)
                                <div style="margin-top: 6px">
                                    <img src="{{ asset('storage/' . $profile->logo_right_path) }}" alt="Logo Kanan" style="height: 60px; object-fit: contain">
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                {{-- Seksi 4: Branding Aplikasi --}}
                <section>
                    <div style="font-size: 14px; font-weight: 700; margin: 0 0 12px; color: var(--sidebar); text-transform: uppercase; letter-spacing: .03em">
                        Branding Aplikasi
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px">
                        <div class="f">
                            <label for="app_name">Nama Aplikasi</label>
                            <input id="app_name" name="app_name" class="in" value="{{ old('app_name', $setting->app_name) }}" required>
                            <span class="hint">Tampil di header, sidebar, dan halaman login</span>
                        </div>
                        <div class="f">
                            <label for="app_subtitle">Subjudul</label>
                            <input id="app_subtitle" name="app_subtitle" class="in" value="{{ old('app_subtitle', $setting->app_subtitle) }}">
                        </div>
                        <div class="f">
                            <label for="topbar_color">Warna Topbar</label>
                            <div style="display: flex; gap: 8px; align-items: center">
                                <input type="color" value="{{ old('topbar_color', $setting->topbar_color) }}" onchange="document.getElementById('topbar_color').value = this.value" style="width: 44px; height: 38px; padding: 0; border: 1px solid var(--border); border-radius: 4px; cursor: pointer">
                                <input id="topbar_color" name="topbar_color" class="in" value="{{ old('topbar_color', $setting->topbar_color) }}" required>
                            </div>
                        </div>
                        <div class="f">
                            <label for="logo_app">Logo Aplikasi (Sidebar & Login)</label>
                            <input id="logo_app" name="logo_app" type="file" accept="image/*" class="in" style="padding: 4px 10px">
                        </div>
                        <div class="f" style="grid-column: 1 / -1">
                            <label for="login_image">Gambar Halaman Login (Landscape)</label>
                            <input id="login_image" name="login_image" type="file" accept="image/*" class="in" style="padding: 4px 10px">
                            <span class="hint">Foto gedung atau ilustrasi madrasah (maks 2MB)</span>
                        </div>
                    </div>
                </section>

                {{-- Seksi 5: Pratinjau Kop --}}
                <section>
                    <div style="font-size: 14px; font-weight: 700; margin: 0 0 12px; color: var(--sidebar); text-transform: uppercase; letter-spacing: .03em">
                        Pratinjau Kop
                    </div>
                    <div style="border: 1px solid var(--border); border-radius: 4px; padding: 16px 20px; background: #fff; overflow-x: auto">
                        <x-kop
                            :parentInstitution="$profile->parent_institution"
                            :madrasahName="$profile->name"
                            :address="$profile->address"
                            :district="$profile->district"
                            :regency="$profile->regency"
                            :province="$profile->province"
                            :logoLeft="$profile->logo_left_path ? asset('storage/' . $profile->logo_left_path) : null"
                            :logoRight="$profile->logo_right_path ? asset('storage/' . $profile->logo_right_path) : null"
                        />
                    </div>
                </section>
            </div>
        </div>
    </form>
</x-layout.app>
