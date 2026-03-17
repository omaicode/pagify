@php
    $actions = is_array($actions ?? null) ? $actions : [];
    $stacked = (bool) ($stacked ?? false);
    $spacing = isset($spacing) ? (int) $spacing : 10;

    $buttonBg = (string) ($buttonBg ?? '#1d4ed8');
    $buttonText = (string) ($buttonText ?? '#ffffff');
    $secondaryBg = (string) ($secondaryBg ?? '#ffffff');
    $secondaryText = (string) ($secondaryText ?? '#1d4ed8');
    $secondaryBorder = (string) ($secondaryBorder ?? '#93c5fd');
@endphp

@if ($actions !== [])
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 16px;">
        <tr>
            <td>
                @foreach ($actions as $index => $action)
                    @php
                        $url = (string) ($action['url'] ?? '#');
                        $label = (string) ($action['label'] ?? 'Open');
                        $variant = (string) ($action['variant'] ?? 'primary');
                        $isLast = $index === count($actions) - 1;

                        $bgColor = $variant === 'secondary' ? $secondaryBg : $buttonBg;
                        $textColor = $variant === 'secondary' ? $secondaryText : $buttonText;
                        $borderColor = $variant === 'secondary' ? $secondaryBorder : 'transparent';
                        $display = $stacked ? 'block' : 'inline-block';
                        $marginRight = $stacked || $isLast ? 0 : $spacing;
                        $marginBottom = $stacked && ! $isLast ? $spacing : 0;
                    @endphp

                    <a href="{{ $url }}" style="display:{{ $display }};background:{{ $bgColor }};color:{{ $textColor }};text-decoration:none;font-size:14px;font-weight:600;padding:12px 18px;border-radius:8px;border:1px solid {{ $borderColor }};margin-right:{{ $marginRight }}px;margin-bottom:{{ $marginBottom }}px;">{{ $label }}</a>
                @endforeach
            </td>
        </tr>
    </table>
@endif
