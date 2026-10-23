@php
    $roleValue = $user->role->value;
@endphp
<x-layout.app title="Profil Saya" :userRole="$roleValue">
    <x-slot:sidebar>
        @if($roleValue === 'operator')
            <x-sidebar.operator active="akun" />
        @elseif($roleValue === 'kepala')
            <x-sidebar.kepala active="akun" />
        @else
            <x-sidebar.guru active="akun" />
        @endif
    </x-slot:sidebar>

    <x-slot:breadcrumb>
        <a href="{{ route($roleValue.'.dashboard') }}">Home</a> / <span>Profil Saya</span>
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

    <x-card title="Foto Profil">
        <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap">
            @if($user->photo_path && file_exists(storage_path('app/public/'.$user->photo_path)))
                <img src="{{ asset('storage/'.$user->photo_path) }}" alt="Foto profil" style="width: 96px; height: 96px; border-radius: 50%; object-fit: cover">
            @else
                <div class="ava" style="width: 96px; height: 96px; font-size: 28px">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            @endif

            <div>
                <p style="margin: 0 0 4px"><b>{{ $user->name }}</b></p>
                <p style="margin: 0 0 12px" class="n">Username: {{ $user->username }}</p>
                <form method="POST" action="{{ route('akun.foto') }}" enctype="multipart/form-data" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center">
                    @csrf
                    <label for="photo" class="sr-only" style="position: absolute; left: -9999px">Pilih foto</label>
                    <input id="photo" name="photo" type="file" accept="image/png,image/jpeg,image/webp" class="in" style="padding: 4px 10px; max-width: 280px" required>
                    <button type="submit" class="btn b-pri">Unggah Foto</button>
                </form>
                <small>JPG, PNG, atau WEBP, maksimal 2 MB.</small>
                @if($user->photo_path)
                    <form method="POST" action="{{ route('akun.foto.hapus') }}" style="margin-top: 8px">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn b-dan" style="min-height: 28px; padding: 0 8px; font-size: 12px">Hapus Foto</button>
                    </form>
                @endif
            </div>
        </div>
    </x-card>

    <x-card title="Ganti Password">
        <form method="POST" action="{{ route('akun.password') }}" style="max-width: 420px">
            @csrf
            @method('PUT')
            <div class="f">
                <label for="current_password">Password Saat Ini</label>
                <input id="current_password" name="current_password" type="password" class="in" autocomplete="current-password" required>
            </div>
            <div class="f">
                <label for="new_password">Password Baru (minimal 6 karakter)</label>
                <input id="new_password" name="password" type="password" class="in" autocomplete="new-password" required>
            </div>
            <div class="f">
                <label for="password_confirmation">Ulangi Password Baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="in" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn b-pri">Simpan Password</button>
        </form>
    </x-card>
</x-layout.app>
