@php
    $url = (string) ($url ?? '#');
    $label = (string) ($label ?? 'Open');
    $marginBottom = isset($marginBottom) ? (int) $marginBottom : 24;
    $bgColor = (string) ($bgColor ?? '#1d4ed8');
@endphp

<p style="margin:0 0 {{ $marginBottom }}px;">
    <a href="{{ $url }}" style="display:inline-block;background:{{ $bgColor }};color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 18px;border-radius:8px;">{{ $label }}</a>
</p>
