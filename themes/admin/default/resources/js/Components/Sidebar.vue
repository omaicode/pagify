<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const navigationLabel = computed(() => page.props.translations?.ui?.navigation ?? 'Navigation');
</script>

<template>
    <nav class="space-y-1">
        <p class="mb-3 text-xs uppercase tracking-wide text-slate-500">{{ navigationLabel }}</p>
        <a
            v-for="item in items"
            :key="item.route ?? item.href"
            :href="item.href"
            class="block rounded px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"
        >
            {{ item.label }}
        </a>
    </nav>
</template>
