@php
    $mailUi = is_array($mailUi ?? null) ? $mailUi : [];
    $brand = is_array($mailUi['brand'] ?? null) ? $mailUi['brand'] : [];
    $styles = is_array($mailUi['styles'] ?? null) ? $mailUi['styles'] : [];

    $pageBg = (string) ($styles['page_bg'] ?? '#f3f4f6');
    $cardBg = (string) ($styles['card_bg'] ?? '#ffffff');
    $cardBorder = (string) ($styles['card_border'] ?? '#e5e7eb');
    $headerBg = (string) ($styles['header_bg'] ?? '#f9fafb');
    $headerText = (string) ($styles['header_text'] ?? '#111827');

    $brandName = (string) ($brand['name'] ?? ($header ?? config('app.name', 'Pagify')));
    $logoUrl = $brand['logo_url'] ?? null;
    $logoAlt = (string) ($brand['logo_alt'] ?? $brandName);
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Pagify') }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $pageBg }};color:#111827;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:{{ $pageBg }};padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:{{ $cardBg }};border:1px solid {{ $cardBorder }};border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px;border-bottom:1px solid {{ $cardBorder }};background:{{ $headerBg }};">
                            @include('core::emails.components.header-logo', [
                                'brandName' => $brandName,
                                'logoUrl' => $logoUrl,
                                'logoAlt' => $logoAlt,
                                'headerTextColor' => $headerText,
                            ])
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            {{ $slot }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
