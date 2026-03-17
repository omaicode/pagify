@php
    $background = (string) ($background ?? '#eff6ff');
    $borderColor = (string) ($borderColor ?? '#bfdbfe');
    $textColor = (string) ($textColor ?? '#1e3a8a');
    $padding = isset($padding) ? (int) $padding : 12;
    $marginBottom = isset($marginBottom) ? (int) $marginBottom : 16;
@endphp

<div style="margin:0 0 {{ $marginBottom }}px;padding:{{ $padding }}px;background:{{ $background }};border:1px solid {{ $borderColor }};border-radius:8px;color:{{ $textColor }};font-size:14px;line-height:1.65;">
    {{ $slot }}
</div>
