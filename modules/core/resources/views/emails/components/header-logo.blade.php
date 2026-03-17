@php
    $brandName = (string) ($brandName ?? config('app.name', 'Pagify'));
    $logoUrl = $logoUrl ?? null;
    $logoAlt = (string) ($logoAlt ?? $brandName);
    $headerTextColor = (string) ($headerTextColor ?? '#111827');
@endphp

<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr>
        @if (is_string($logoUrl) && $logoUrl !== '')
            <td style="width:48px;vertical-align:middle;padding-right:12px;">
                <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" style="display:block;max-width:48px;height:auto;border:0;">
            </td>
        @endif
        <td style="vertical-align:middle;">
            <h1 style="margin:0;font-size:20px;line-height:1.3;font-weight:700;color:{{ $headerTextColor }};">{{ $brandName }}</h1>
        </td>
    </tr>
</table>
