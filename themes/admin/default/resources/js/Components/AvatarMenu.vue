<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    initials: {
        type: String,
        default: 'A',
    },
    profileHref: {
        type: String,
        default: '#',
    },
    settingsHref: {
        type: String,
        default: '#',
    },
});

const emit = defineEmits(['logout']);

const opened = ref(false);

const onDocumentClick = (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement)) {
        opened.value = false;
        return;
    }

    if (target.closest('[data-avatar-dropdown]')) {
        return;
    }

    opened.value = false;
};

const onWindowKeydown = (event) => {
    if (event.key === 'Escape') {
        opened.value = false;
    }
};

const logout = () => {
    opened.value = false;
    emit('logout');
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
    <div class="relative" data-avatar-dropdown>
        <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-semibold text-white cursor-pointer"
            style="background-image: var(--pagify-gradient)"
            @click="opened = !opened"
        >
            {{ initials }}
        </button>

        <div
            v-if="opened"
            class="absolute right-0 z-50 mt-2 min-w-[150px] rounded-xl border border-[#e5deff] bg-white p-1.5 shadow-lg"
        >
            <a :href="profileHref" class="block rounded-lg px-3 py-2 text-sm text-[#1e1b4b] hover:bg-[#f3f0ff]">Hồ sơ</a>
            <a :href="settingsHref" class="block rounded-lg px-3 py-2 text-sm text-[#1e1b4b] hover:bg-[#f3f0ff]">Cài đặt</a>
            <button
                type="button"
                class="block w-full rounded-lg px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50"
                @click="logout"
            >
                Thoát
            </button>
        </div>
    </div>
</template>
