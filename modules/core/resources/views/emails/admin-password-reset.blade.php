@php
    $mailUi = is_array($mailUi ?? null) ? $mailUi : [];
    $styles = is_array($mailUi['styles'] ?? null) ? $mailUi['styles'] : [];
@endphp

@component('core::emails.components.layout', ['title' => $appName, 'header' => $appName, 'mailUi' => $mailUi])
    @component('core::emails.components.paragraph', ['size' => 'md', 'marginBottom' => 16])
        {{ $greeting }}
    @endcomponent

    @component('core::emails.components.paragraph', ['tone' => 'muted', 'marginBottom' => 16])
        {{ $intro }}
    @endcomponent

    @include('core::emails.components.divider', [
        'color' => $styles['divider'] ?? '#e5e7eb',
        'marginTop' => 8,
        'marginBottom' => 16,
    ])

    @include('core::emails.components.cta-group', [
        'actions' => [
            [
                'url' => $resetUrl,
                'label' => $ctaLabel,
                'variant' => 'primary',
            ],
        ],
        'buttonBg' => $styles['button_bg'] ?? '#1d4ed8',
        'buttonText' => $styles['button_text'] ?? '#ffffff',
        'secondaryBg' => $styles['card_bg'] ?? '#ffffff',
        'secondaryText' => $styles['button_bg'] ?? '#1d4ed8',
        'secondaryBorder' => $styles['divider'] ?? '#e5e7eb',
    ])

    @component('core::emails.components.info-box', [
        'background' => $styles['info_box_bg'] ?? '#eff6ff',
        'borderColor' => $styles['info_box_border'] ?? '#bfdbfe',
        'textColor' => $styles['info_box_text'] ?? '#1e3a8a',
        'marginBottom' => 16,
    ])
        <strong style="display:block;margin-bottom:6px;">{{ $expiryText }}</strong>
        <span>{{ $ignoreText }}</span>
    @endcomponent

    @include('core::emails.components.link-fallback', ['url' => $resetUrl])

    @include('core::emails.components.footer-signature', [
        'brandName' => $mailUi['brand']['name'] ?? $appName,
        'noteText' => $footerSignatureNote ?? '',
        'contactText' => $footerContactText ?? '',
        'dividerColor' => $styles['divider'] ?? '#e5e7eb',
        'textColor' => $styles['subtle_text'] ?? '#6b7280',
    ])
@endcomponent
