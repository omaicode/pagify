<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});

const opened = ref(false);
const query = ref('');

const filtered = computed(() => {
    if (query.value.trim() === '') {
        return [];
    }

    return (Array.isArray(props.items) ? props.items : [])
        .filter((item) => item.label?.toLowerCase().includes(query.value.toLowerCase()))
        .slice(0, 8);
});

const onKeydown = (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        opened.value = !opened.value;
    }

    if (event.key === 'Escape') {
        opened.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="rounded-full border border-[#7c3aed] bg-[#f3f0ff] px-3 py-1.5 text-xs font-medium text-[#4b3fd8]"
            @click="opened = !opened"
        >
            {{ t.search ?? 'Search (⌘K)' }}
        </button>

        <div
            v-if="opened"
            class="absolute right-0 z-50 mt-2 w-80 rounded-xl border border-[#e5deff] bg-white p-3 shadow-lg"
            style="border-left: 3px solid #a020f0"
        >
            <input
                v-model="query"
                type="text"
                :placeholder="t.search_menu ?? 'Search menu...'"
                class="pf-input mb-2"
            >
            <div class="space-y-1">
                <a
                    v-for="item in filtered"
                    :key="item.route ?? item.href"
                    :href="item.href"
                    class="block rounded-lg px-2 py-1.5 text-sm text-[#1e1b4b] hover:bg-[#f3f0ff]"
                >
                    {{ item.label }}
                </a>
                <p v-if="filtered.length === 0" class="px-2 py-1 text-xs text-[#6b7280]">{{ t.no_results ?? 'No results' }}</p>
            </div>
        </div>
    </div>
</template>
