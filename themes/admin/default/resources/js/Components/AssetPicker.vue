<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: [String, Number, null],
        default: null,
    },
    assetsIndexUrl: {
        type: String,
        default: '/api/v1/admin/media/assets',
    },
});

const emit = defineEmits(['update:modelValue']);

const opened = ref(false);
const loading = ref(false);
const search = ref('');
const errorMessage = ref('');
const assets = ref([]);

const currentValue = computed(() => {
    const value = props.modelValue;

    if (value === null || value === undefined) {
        return '';
    }

    return String(value);
});

const currentAsset = computed(() => {
    if (!currentValue.value) {
        return null;
    }

    return assets.value.find((asset) => String(asset.uuid) === currentValue.value) ?? null;
});

const loadAssets = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.get(props.assetsIndexUrl, {
            params: {
                q: search.value || undefined,
                per_page: 20,
                sort_by: 'created_at',
                sort_dir: 'desc',
            },
        });

        assets.value = response.data?.data ?? [];
    } catch (error) {
        assets.value = [];
        errorMessage.value = error?.response?.data?.message ?? 'Failed to load media assets.';
    } finally {
        loading.value = false;
    }
};

const open = async () => {
    opened.value = true;
    await loadAssets();
};

const close = () => {
    opened.value = false;
};

const selectAsset = (asset) => {
    emit('update:modelValue', String(asset.uuid));
    close();
};

const clearAsset = () => {
    emit('update:modelValue', '');
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-700" @click="open">
                {{ currentValue ? 'Change asset' : 'Select asset' }}
            </button>
            <button v-if="currentValue" type="button" class="rounded border border-rose-300 px-3 py-1.5 text-sm text-rose-700" @click="clearAsset">
                Clear
            </button>
        </div>

        <p v-if="currentAsset" class="text-xs text-slate-600">
            Selected: {{ currentAsset.original_name }} ({{ currentAsset.uuid }})
        </p>
        <p v-else-if="currentValue" class="text-xs text-slate-600">Selected asset UUID: {{ currentValue }}</p>
        <p v-else class="text-xs text-slate-500">No asset selected.</p>

        <div v-if="opened" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 px-4" @click.self="close">
            <div class="w-full max-w-4xl rounded-xl border border-slate-200 bg-white p-4 shadow-xl">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">Select media asset</h3>
                    <button type="button" class="rounded border border-slate-300 px-2 py-1 text-xs" @click="close">Close</button>
                </div>

                <div class="mb-3 flex items-center gap-2">
                    <input
                        v-model="search"
                        type="text"
                        class="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
                        placeholder="Search media..."
                        @keydown.enter.prevent="loadAssets"
                    >
                    <button type="button" class="rounded border border-slate-300 px-3 py-1.5 text-sm" :disabled="loading" @click="loadAssets">Search</button>
                </div>

                <p v-if="errorMessage" class="mb-3 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    {{ errorMessage }}
                </p>

                <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Loading...</div>

                <div v-else class="max-h-[60vh] overflow-auto">
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <button
                            v-for="asset in assets"
                            :key="asset.id"
                            type="button"
                            class="rounded border border-slate-200 p-3 text-left hover:border-slate-300"
                            @click="selectAsset(asset)"
                        >
                            <p class="truncate text-sm font-medium text-slate-900" :title="asset.original_name">{{ asset.original_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ asset.mime_type || '-' }}</p>
                            <p class="text-xs text-slate-500">{{ asset.uuid }}</p>
                        </button>
                    </div>

                    <div v-if="assets.length === 0" class="py-8 text-center text-sm text-slate-500">No assets found.</div>
                </div>
            </div>
        </div>
    </div>
</template>
