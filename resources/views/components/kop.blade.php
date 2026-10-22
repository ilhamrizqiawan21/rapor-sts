@props([
    'parentInstitution' => null,
    'madrasahName' => 'Nama Madrasah',
    'address' => null,
    'district' => null,
    'regency' => null,
    'province' => null,
    'logoLeft' => null,
    'logoRight' => null,
])

<div {{ $attributes->merge(['class' => 'kop-wrapper']) }} style="display: flex; align-items: center; gap: 16px; padding-bottom: 6px">
    <div style="width: 78px; height: 78px; display: flex; align-items: center; justify-content: center; flex: none">
        @if($logoLeft)
            <img src="{{ $logoLeft }}" alt="Logo Kiri" style="max-width: 78px; max-height: 78px; object-fit: contain">
        @else
            <div style="width: 78px; height: 78px; border: 1px dashed #777; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #555; text-align: center">
                LOGO<br>KOP
            </div>
        @endif
    </div>

    <div style="flex: 1; text-align: center; @if(!$logoRight) padding-right: 78px; @endif">
        @if($parentInstitution)
            <div style="font-family: Arial, Helvetica, sans-serif; font-weight: 700; font-size: 12.5px; text-transform: uppercase">
                {{ $parentInstitution }}
            </div>
        @endif
        <div style="font-family: 'Times New Roman', Times, serif; font-weight: 700; font-size: 18px; text-transform: uppercase">
            {{ $madrasahName }}
        </div>
        @if($address && trim($address) !== '-')
            <div style="font-family: 'Times New Roman', Times, serif; font-style: italic; font-size: 12.5px">
                {{ $address }}
            </div>
        @endif
        @php($regionLine = \App\Models\MadrasahProfile::formatRegion($district, $regency, $province))
        @if($regionLine !== '')
            <div style="font-family: 'Times New Roman', Times, serif; font-style: italic; font-size: 12.5px">
                {{ $regionLine }}
            </div>
        @endif
    </div>

    @if($logoRight)
        <div style="width: 78px; height: 78px; display: flex; align-items: center; justify-content: center; flex: none">
            <img src="{{ $logoRight }}" alt="Logo Kanan" style="max-width: 78px; max-height: 78px; object-fit: contain">
        </div>
    @endif
</div>

