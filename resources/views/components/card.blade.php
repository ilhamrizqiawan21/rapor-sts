@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if($title || isset($actions))
        <div class="ch">
            <div>
                @if($title)
                    <div class="ct">{{ $title }}</div>
                @endif
                @if($subtitle)
                    <div class="cs">{{ $subtitle }}</div>
                @endif
            </div>

            @if(isset($actions))
                <div class="acts">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="cb">
        {{ $slot }}
    </div>
</div>
