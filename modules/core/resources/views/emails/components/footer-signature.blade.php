@php
    $brandName = (string) ($brandName ?? config('app.name', 'Pagify'));
    $noteText = (string) ($noteText ?? '');
    $contactText = (string) ($contactText ?? '');
    $dividerColor = (string) ($dividerColor ?? '#e5e7eb');
    $textColor = (string) ($textColor ?? '#6b7280');
@endphp

@include('core::emails.components.divider', [
    'color' => $dividerColor,
    'marginTop' => 12,
    'marginBottom' => 12,
])

<p style="margin:0 0 8px;font-size:13px;line-height:1.65;color:{{ $textColor }};">
    {{ $brandName }}
</p>

@if ($noteText !== '')
    <p style="margin:0 0 6px;font-size:12px;line-height:1.65;color:{{ $textColor }};">
        {{ $noteText }}
    </p>
@endif

@if ($contactText !== '')
    <p style="margin:0;font-size:12px;line-height:1.65;color:{{ $textColor }};">
        {{ $contactText }}
    </p>
@endif
