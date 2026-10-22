@props([
    'showEntries' => true,
    'search' => true,
    'buttons' => ['copy', 'print', 'excel'],
    'searchPlaceholder' => 'Cari...',
])

<div class="tools">
    @if(!empty($buttons))
        <div style="display: flex; gap: 4px">
            @if(in_array('copy', $buttons))
                <button type="button" class="tbtn" onclick="window.print ? null : null">Copy</button>
            @endif
            @if(in_array('print', $buttons))
                <button type="button" class="tbtn" onclick="window.print()">Print</button>
            @endif
            @if(in_array('excel', $buttons))
                <button type="button" class="tbtn">Excel</button>
            @endif
        </div>
    @endif

    @if($showEntries)
        <label style="display: flex; align-items: center; gap: 6px">
            Show
            <select class="sel" style="min-width: 60px; height: 32px">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            entries
        </label>
    @endif

    @if($search)
        <label style="margin-left: auto; display: flex; align-items: center; gap: 6px">
            Search:
            <input class="in" style="width: 180px; height: 32px" type="search" placeholder="{{ $searchPlaceholder }}">
        </label>
    @endif

    {{ $slot }}
</div>
