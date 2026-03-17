@php
    $color = (string) ($color ?? '#e5e7eb');
    $marginTop = isset($marginTop) ? (int) $marginTop : 16;
    $marginBottom = isset($marginBottom) ? (int) $marginBottom : 16;
@endphp

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:{{ $marginTop }}px 0 {{ $marginBottom }}px;">
    <tr>
        <td style="height:1px;background:{{ $color }};font-size:0;line-height:0;">&nbsp;</td>
    </tr>
</table>
