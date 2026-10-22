@props([
    'status' => 'belum_kirim', // terkirim, belum_kirim, dikunci, terbuka
    'label' => null,
])

@php
    $normalized = strtolower(str_replace(['-', ' '], '_', $status));

    $config = match($normalized) {
        'terkirim', 'submitted' => [
            'bg' => 'var(--success)',
            'icon' => '✓',
            'defaultLabel' => 'Terkirim',
        ],
        'belum_kirim', 'draft', 'pending' => [
            'bg' => 'var(--danger)',
            'icon' => '✕',
            'defaultLabel' => 'Belum Kirim',
        ],
        'dikunci', 'locked' => [
            'bg' => 'var(--info)',
            'icon' => '🔒',
            'defaultLabel' => 'Dikunci',
        ],
        'terbuka', 'unlocked' => [
            'bg' => 'var(--success)',
            'icon' => '🔓',
            'defaultLabel' => 'Terbuka',
        ],
        default => [
            'bg' => 'var(--muted)',
            'icon' => '•',
            'defaultLabel' => ucfirst($status),
        ],
    };

    $displayLabel = $label ?? $config['defaultLabel'];
@endphp

<span {{ $attributes->merge(['class' => 'pill']) }} style="background: {{ $config['bg'] }}">
    {{ $config['icon'] }} {{ $displayLabel }}
</span>
