<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    apiRoutes: {
        type: Object,
        required: true,
    },
    defaultViewMode: {
        type: String,
        default: 'grid',
    },
});

const loading = ref(false);
const uploading = ref(false);
const assets = ref([]);
const pagination = ref({ current_page: 1, per_page: 24, total: 0, last_page: 1 });
const errorMessage = ref('');

const filters = reactive({
    q: '',
    kind: '',
    view: props.defaultViewMode,
});

const activeView = computed(() => (filters.view === 'list' ? 'list' : 'grid'));

const loadAssets = async (page = 1) => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.get(props.apiRoutes.assetsIndex, {
            params: {
                q: filters.q || undefined,
                kind: filters.kind || undefined,
                view: activeView.value,
                page,
                per_page: pagination.value.per_page,
            },
        });

        assets.value = response.data?.data ?? [];
        pagination.value = {
            ...pagination.value,
            ...(response.data?.meta?.pagination ?? {}),
        };
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Failed to load media assets.';
    } finally {
        loading.value = false;
    }
};

const onUpload = async (event) => {
    const file = event.target.files?.[0] ?? null;
    if (file === null) {
        return;
    }

    uploading.value = true;
    errorMessage.value = '';

    try {
        const formData = new FormData();
        formData.append('file', file);

        await axios.post(props.apiRoutes.assetsStore, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        await loadAssets(1);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Failed to upload file.';
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
};

const removeAsset = async (asset) => {
    try {
        await axios.delete(props.apiRoutes.assetsDestroyBase.replace('__ASSET__', String(asset.id)));
        await loadAssets(pagination.value.current_page);
    } catch (error) {
        const code = error?.response?.data?.code;
        if (code === 'ASSET_IN_USE') {
            errorMessage.value = `${error?.response?.data?.message ?? 'Asset is in use.'} Use force delete from API in this phase.`;
            return;
        }

        errorMessage.value = error?.response?.data?.message ?? 'Failed to delete file.';
    }
};

onMounted(() => {
    loadAssets();
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="pf-section-title">Media library</h1>
                    <p class="pf-section-subtitle">Manage shared images, videos, and documents.</p>
                </div>

                <label class="pf-btn-primary cursor-pointer">
                    <span>{{ uploading ? 'Uploading...' : 'Upload file' }}</span>
                    <input type="file" class="hidden" :disabled="uploading" @change="onUpload">
                </label>
            </div>

            <div class="pf-card flex flex-wrap items-center gap-2">
                <input
                    v-model="filters.q"
                    type="text"
                    class="pf-input max-w-sm"
                    placeholder="Search name, filename, mime..."
                    @keydown.enter.prevent="loadAssets(1)"
                >

                <select v-model="filters.kind" class="pf-input max-w-[180px]" @change="loadAssets(1)">
                    <option value="">All types</option>
                    <option value="image">Image</option>
                    <option value="video">Video</option>
                    <option value="document">Document</option>
                    <option value="other">Other</option>
                </select>

                <select v-model="filters.view" class="pf-input max-w-[140px]" @change="loadAssets(1)">
                    <option value="grid">Grid</option>
                    <option value="list">List</option>
                </select>

                <button type="button" class="pf-btn-outline" :disabled="loading" @click="loadAssets(1)">Apply</button>
            </div>

            <div v-if="errorMessage" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {{ errorMessage }}
            </div>

            <div v-if="loading" class="pf-card text-sm text-slate-500">Loading...</div>

            <div v-else-if="activeView === 'grid'" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <article v-for="asset in assets" :key="asset.id" class="pf-card">
                    <p class="truncate text-sm font-medium text-[#1e1b4b]" :title="asset.original_name">{{ asset.original_name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ asset.mime_type || '-' }}</p>
                    <p class="text-xs text-slate-500">{{ asset.size_bytes }} bytes</p>
                    <p class="text-xs text-slate-500">
                        Transforms:
                        {{ (asset.transforms ?? []).map((item) => `${item.profile}:${item.status}`).join(', ') || 'none' }}
                    </p>
                    <div class="mt-3 flex gap-2">
                        <button type="button" class="pf-btn-outline !px-2 !py-1 !text-xs" @click="removeAsset(asset)">Delete</button>
                    </div>
                </article>

                <article v-if="assets.length === 0" class="pf-card text-sm text-slate-500 sm:col-span-2 lg:col-span-4">
                    No media assets yet.
                </article>
            </div>

            <div v-else class="pf-card overflow-hidden p-0">
                <table class="min-w-full divide-y divide-[#ece8ff] text-sm">
                    <thead class="bg-[#f8f6ff]">
                        <tr>
                            <th class="px-3 py-2 text-left">Name</th>
                            <th class="px-3 py-2 text-left">Type</th>
                            <th class="px-3 py-2 text-left">Size</th>
                            <th class="px-3 py-2 text-left">Created at</th>
                            <th class="px-3 py-2 text-left">Transforms</th>
                            <th class="px-3 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="asset in assets" :key="asset.id">
                            <td class="px-3 py-2">{{ asset.original_name }}</td>
                            <td class="px-3 py-2">{{ asset.mime_type || '-' }}</td>
                            <td class="px-3 py-2">{{ asset.size_bytes }}</td>
                            <td class="px-3 py-2">{{ asset.created_at || '-' }}</td>
                            <td class="px-3 py-2">{{ (asset.transforms ?? []).map((item) => `${item.profile}:${item.status}`).join(', ') || '-' }}</td>
                            <td class="px-3 py-2">
                                <button type="button" class="pf-btn-outline !px-2 !py-1 !text-xs" @click="removeAsset(asset)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="assets.length === 0">
                            <td colspan="6" class="px-3 py-6 text-center text-slate-500">No media assets yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-2 text-sm text-slate-600">
                <span>Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
                <button
                    type="button"
                    class="pf-btn-outline !px-2 !py-1 !text-xs"
                    :disabled="pagination.current_page <= 1 || loading"
                    @click="loadAssets(pagination.current_page - 1)"
                >
                    Prev
                </button>
                <button
                    type="button"
                    class="pf-btn-outline !px-2 !py-1 !text-xs"
                    :disabled="pagination.current_page >= pagination.last_page || loading"
                    @click="loadAssets(pagination.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
