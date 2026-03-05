<script setup>
import { computed } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    currentUrl: {
        type: String,
        default: '/',
    },
    translations: {
        type: Object,
        default: () => ({}),
    },
    overrideItems: {
        type: Array,
        default: () => [],
    },
});

const normalizePath = (value) => {
    if (!value) {
        return '/';
    }

    try {
        const base = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
        const parsedUrl = new URL(String(value), base);
        const normalized = parsedUrl.pathname.replace(/\/+$/, '');

        return normalized || '/';
    } catch {
        const fallbackValue = String(value).split('?')[0].split('#')[0] || '/';
        const normalized = fallbackValue.replace(/\/+$/, '');

        return normalized || '/';
    }
};

const itemPatterns = (item) => {
    if (!item || typeof item !== 'object') {
        return [];
    }

    const rawPatterns = [item.href];

    if (Array.isArray(item.active_patterns)) {
        rawPatterns.push(...item.active_patterns);
    }

    return rawPatterns
        .map((pattern) => normalizePath(pattern))
        .filter((pattern, index, array) => pattern && array.indexOf(pattern) === index);
};

const resolveLabel = (item) => {
    if (item && typeof item === 'object' && typeof item.label_key === 'string' && item.label_key !== '') {
        return props.translations?.[item.label_key] ?? item.label;
    }

    return item?.label ?? '';
};

const normalizedOverrideCrumbs = computed(() => {
    if (!Array.isArray(props.overrideItems) || props.overrideItems.length === 0) {
        return [];
    }

    return props.overrideItems
        .filter((item) => item && typeof item === 'object')
        .map((item) => {
            return {
                href: typeof item.href === 'string' && item.href !== '' ? item.href : null,
                label: resolveLabel(item),
            };
        })
        .filter((item) => item.label !== '');
});

const matchMenuItem = (path) => {
    let bestItem = null;
    let bestScore = -1;

    for (const item of props.items) {
        const patterns = itemPatterns(item);

        for (const pattern of patterns) {
            const matched = pattern === '/'
                ? path === '/'
                : path === pattern || path.startsWith(`${pattern}/`);

            if (!matched) {
                continue;
            }

            if (pattern.length > bestScore) {
                bestItem = item;
                bestScore = pattern.length;
            }
        }
    }

    return bestItem;
};

const segmentLabel = (segment) => {
    const keyMap = {
        settings: 'settings',
        modules: 'settings_item_modules',
        plugins: 'settings_item_plugins',
        'api-tokens': 'settings_item_api_tokens',
        'audit-logs': 'settings_item_audit_logs',
        content: 'settings_item_content',
        updater: 'settings_item_updater',
        media: 'media_nav_library',
        library: 'media_nav_library',
        entries: 'entries',
        types: 'content_types',
        builder: 'builder',
        revisions: 'view_revisions',
        create: 'create_entry',
        edit: 'edit',
        status: 'status',
    };

    const key = keyMap[segment];

    if (key && props.translations?.[key]) {
        return props.translations[key];
    }

    const normalized = segment.replace(/[-_]+/g, ' ').trim();

    if (normalized === '') {
        return segment;
    }

    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

const crumbs = computed(() => {
    if (normalizedOverrideCrumbs.value.length > 0) {
        return normalizedOverrideCrumbs.value;
    }

    const path = normalizePath(props.currentUrl);
    const result = [];

    const dashboardItem = props.items.find((item) => normalizePath(item?.href) === '/admin')
        ?? props.items.find((item) => item?.label_key === 'dashboard')
        ?? null;

    const dashboardLabel = dashboardItem ? resolveLabel(dashboardItem) : (props.translations?.dashboard ?? 'Dashboard');

    result.push({
        href: dashboardItem?.href ?? '/admin',
        label: dashboardLabel,
    });

    if (path === '/admin') {
        return result;
    }

    const matched = matchMenuItem(path);

    if (matched && normalizePath(matched.href) !== '/admin') {
        result.push({
            href: matched.href,
            label: resolveLabel(matched),
        });
    }

    const basePath = matched ? normalizePath(matched.href) : '/admin';
    const baseSegments = basePath.split('/').filter(Boolean);
    const pathSegments = path.split('/').filter(Boolean);

    let partial = basePath;

    for (let index = baseSegments.length; index < pathSegments.length; index += 1) {
        const segment = pathSegments[index];

        if (/^\d+$/.test(segment)) {
            continue;
        }

        partial = `${partial.replace(/\/+$/, '')}/${segment}`;

        const exists = result.some((item) => normalizePath(item.href) === normalizePath(partial));
        if (exists) {
            continue;
        }

        result.push({
            href: partial,
            label: segmentLabel(segment),
        });
    }

    return result;
});
</script>

<template>
    <nav class="mb-4" :aria-label="translations?.breadcrumb ?? 'Breadcrumb'">
        <ol class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <li v-for="(crumb, index) in crumbs" :key="`${crumb.href}-${index}`" class="flex items-center gap-2">
                <a
                    v-if="index < crumbs.length - 1 && crumb.href"
                    :href="crumb.href"
                    class="transition hover:text-[#4338ca]"
                >
                    {{ crumb.label }}
                </a>
                <span v-else class="font-medium text-[#1e1b4b]">{{ crumb.label }}</span>
                <span v-if="index < crumbs.length - 1" aria-hidden="true" class="text-slate-400">/</span>
            </li>
        </ol>
    </nav>
</template>
