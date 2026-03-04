<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
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
const triggerRef = ref(null);
const panelStyle = ref({});

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

const updatePanelPosition = () => {
    const trigger = triggerRef.value;

    if (!trigger) {
        return;
    }

    const rect = trigger.getBoundingClientRect();
    const panelWidth = Math.min(320, window.innerWidth - 16);
    const desiredLeft = rect.right - panelWidth;
    const left = Math.max(8, Math.min(desiredLeft, window.innerWidth - panelWidth - 8));

    panelStyle.value = {
        left: `${left}px`,
        top: `${rect.bottom + 8}px`,
        width: `${panelWidth}px`,
    };
};

const toggleOpened = async () => {
    opened.value = !opened.value;

    if (opened.value) {
        await nextTick();
        updatePanelPosition();
    }
};

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', updatePanelPosition);
    window.addEventListener('scroll', updatePanelPosition, true);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', updatePanelPosition);
    window.removeEventListener('scroll', updatePanelPosition, true);
});
</script>

<template>
    <div class="relative">
        <button
            ref="triggerRef"
            type="button"
            class="rounded-full border border-[#d8cffc] bg-white px-2.5 py-1.5 text-xs font-medium flex items-center gap-1 text-gray-700 hover:bg-[#f3f0ff] cursor-pointer"
            @click="toggleOpened"
        >
            <svg viewBox="0 0 24 24" class="h-4 w-4 inline-block" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7" />
                <path d="M20 20L16.65 16.65" />
            </svg>
            <span class="inline-block">
                (⌘K)
            </span>
        </button>

        <div
            v-if="opened"
            class="fixed z-[70] rounded-xl border border-[#e5deff] bg-white p-3 shadow-lg"
            :style="panelStyle"
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
