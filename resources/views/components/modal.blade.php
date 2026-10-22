@props([
    'id',
    'title',
    'submitLabel' => 'Save changes',
    'cancelLabel' => 'Batal',
    'maxWidth' => '900px',
])

<dialog id="{{ $id }}" {{ $attributes->merge(['class' => 'modal-dialog']) }} style="max-width: {{ $maxWidth }};">
    <div style="display: flex; align-items: center; padding: 16px 20px; border-bottom: 1px solid var(--border)">
        <h2 style="margin: 0; font-size: 19px; font-weight: 600">{{ $title }}</h2>
        <button type="button" aria-label="Tutup" onclick="document.getElementById('{{ $id }}').close()" style="margin-left: auto; width: 36px; height: 36px; border: 0; background: none; font-size: 22px; cursor: pointer; color: var(--muted); display: flex; align-items: center; justify-content: center">×</button>
    </div>

    <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div style="display: flex; justify-content: flex-end; gap: 8px; padding: 14px 20px; border-top: 1px solid var(--border)">
            {{ $footer }}
        </div>
    @else
        <div style="display: flex; justify-content: flex-end; gap: 8px; padding: 14px 20px; border-top: 1px solid var(--border)">
            <button type="button" class="btn b-dan" onclick="document.getElementById('{{ $id }}').close()">{{ $cancelLabel }}</button>
            <button type="submit" class="btn b-pri">{{ $submitLabel }}</button>
        </div>
    @endif
</dialog>
