<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    locale: {
        type: String,
        default: 'en',
    },
    supportedLocales: {
        type: Array,
        default: () => ['en'],
    },
});

const emit = defineEmits(['select']);

const opened = ref(false);

const localeFlagMap = {
    en: '🇺🇸',
    vi: '🇻🇳',
};

const localeLabel = (code) => `${localeFlagMap[code] ?? '🏳️'} ${String(code).toUpperCase()}`;

const activeLabel = computed(() => localeLabel(props.locale));

const onDocumentClick = (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement)) {
        opened.value = false;
        return;
    }

    if (target.closest('[data-locale-dropdown]')) {
        return;
    }

    opened.value = false;
};

const onWindowKeydown = (event) => {
    if (event.key === 'Escape') {
        opened.value = false;
    }
};

const selectLocale = (value) => {
    opened.value = false;
    emit('select', value);
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('keydown', onWindowKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('keydown', onWindowKeydown);
});
</script>

<template>
    <div class="relative" data-locale-dropdown>
        <button
            type="button"
            class="rounded-full border border-[#d8cffc] bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-[#f3f0ff] cursor-pointer"
            @click="opened = !opened"
        >
            {{ activeLabel }}
        </button>

        <div
            v-if="opened"
            class="absolute right-0 z-50 mt-2 min-w-[120px] rounded-xl border border-[#e5deff] bg-white p-1.5 shadow-lg"
        >
            <button
                v-for="item in supportedLocales"
                :key="item"
                type="button"
                class="block w-full rounded-lg px-2 py-1.5 text-left text-xs text-[#1e1b4b] hover:bg-[#f3f0ff]"
                @click="selectLocale(item)"
            >
                {{ localeLabel(item) }}
            </button>
        </div>
    </div>
</template>
