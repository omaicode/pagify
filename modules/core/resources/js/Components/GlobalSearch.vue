<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

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
            class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-600"
            @click="opened = !opened"
        >
            Search (⌘K)
        </button>

        <div
            v-if="opened"
            class="absolute right-0 z-50 mt-2 w-72 rounded border border-slate-200 bg-white p-2 shadow"
        >
            <input
                v-model="query"
                type="text"
                placeholder="Search menu..."
                class="mb-2 w-full rounded border border-slate-300 px-2 py-1 text-sm"
            >
            <div class="space-y-1">
                <a
                    v-for="item in filtered"
                    :key="item.route ?? item.href"
                    :href="item.href"
                    class="block rounded px-2 py-1 text-sm text-slate-700 hover:bg-slate-100"
                >
                    {{ item.label }}
                </a>
                <p v-if="filtered.length === 0" class="px-2 py-1 text-xs text-slate-500">No results</p>
            </div>
        </div>
    </div>
</template>
