@php
    $size = $size ?? 'md';
    $tone = $tone ?? 'default';
    $marginBottom = isset($marginBottom) ? (int) $marginBottom : 16;

    $fontSize = $size === 'sm' ? '14px' : '15px';
    $lineHeight = $size === 'sm' ? '1.7' : '1.65';
    $color = '#111827';

    if ($tone === 'muted') {
        $color = '#4b5563';
    } elseif ($tone === 'subtle') {
        $color = '#6b7280';
    }
@endphp

<p style="margin:0 0 {{ $marginBottom }}px;font-size:{{ $fontSize }};line-height:{{ $lineHeight }};color:{{ $color }};">
    {{ $slot }}
</p>
