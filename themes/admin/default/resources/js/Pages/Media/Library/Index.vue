<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
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
const previewAsset = ref(null);
const copiedAssetId = ref(null);

const filters = reactive({
    q: '',
    kind: '',
    view: props.defaultViewMode,
});

const activeView = computed(() => (filters.view === 'list' ? 'list' : 'grid'));

const isImageAsset = (asset) => String(asset?.kind ?? '').toLowerCase() === 'image';

const resolveAssetPreviewUrl = (asset) => props.apiRoutes.assetsPreviewBase.replace('__ASSET__', String(asset.id));

const resolveAssetDownloadUrl = (asset) => props.apiRoutes.assetsDownloadBase.replace('__ASSET__', String(asset.id));

const copyAssetUrl = async (asset) => {
    const url = resolveAssetDownloadUrl(asset);

    try {
        await navigator.clipboard.writeText(url);
        copiedAssetId.value = asset.id;
        toast.success('Copied download URL');
        setTimeout(() => {
            if (copiedAssetId.value === asset.id) {
                copiedAssetId.value = null;
            }
        }, 1500);
    } catch (_error) {
        errorMessage.value = 'Failed to copy URL.';
    }
};

const openPreview = (asset) => {
    if (!isImageAsset(asset)) {
        return;
    }

    previewAsset.value = asset;
};

const closePreview = () => {
    previewAsset.value = null;
};

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
                    <div class="relative mb-3 overflow-hidden rounded border border-[#ece8ff] bg-slate-50">
                        <button
                            type="button"
                            class="absolute right-2 top-2 z-10 rounded border p-1 cursor-pointer"
                            :class="copiedAssetId === asset.id
                                ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                : 'border-[#ece8ff] bg-white/90 text-slate-700 hover:bg-white'"
                            :title="copiedAssetId === asset.id ? 'Copied' : 'Copy download URL'"
                            @click="copyAssetUrl(asset)"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M12.586 3.586a2 2 0 0 1 2.828 0l1 1a2 2 0 0 1 0 2.828l-3.172 3.172a2 2 0 0 1-2.828 0 .75.75 0 1 0-1.06 1.06 3.5 3.5 0 0 0 4.948 0l3.172-3.172a3.5 3.5 0 0 0 0-4.95l-1-1a3.5 3.5 0 0 0-4.95 0L8.354 5.414a.75.75 0 1 0 1.06 1.06l3.172-3.172Z" />
                                <path d="M10.646 8.354a3.5 3.5 0 0 0-4.948 0L2.526 11.526a3.5 3.5 0 0 0 0 4.95l1 1a3.5 3.5 0 0 0 4.95 0l3.172-3.172a.75.75 0 1 0-1.06-1.06l-3.172 3.172a2 2 0 0 1-2.828 0l-1-1a2 2 0 0 1 0-2.828l3.172-3.172a2 2 0 0 1 2.828 0 .75.75 0 1 0 1.06-1.06Z" />
                            </svg>
                        </button>
                        <img
                            v-if="isImageAsset(asset)"
                            :src="resolveAssetPreviewUrl(asset)"
                            :alt="asset.alt_text || asset.original_name"
                            class="h-32 w-full object-cover"
                            loading="lazy"
                        >
                        <div v-else class="flex h-32 items-center justify-center text-xs text-slate-500">
                            {{ (asset.extension || asset.kind || 'file').toUpperCase() }}
                        </div>
                    </div>
                    <p class="truncate text-sm font-medium text-[#1e1b4b]" :title="asset.original_name">{{ asset.original_name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ asset.mime_type || '-' }}</p>
                    <p class="text-xs text-slate-500">{{ asset.size_bytes }} bytes</p>
                    <p class="text-xs text-slate-500">
                        Transforms:
                        {{ (asset.transforms ?? []).map((item) => `${item.profile}:${item.status}`).join(', ') || 'none' }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-if="isImageAsset(asset)"
                            type="button"
                            class="pf-btn-outline !px-2 !py-1 !text-xs"
                            @click="openPreview(asset)"
                        >
                            View
                        </button>
                        <a
                            :href="resolveAssetDownloadUrl(asset)"
                            class="pf-btn-outline !px-2 !py-1 !text-xs"
                        >
                            Download
                        </a>
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
                                <button
                                    v-if="isImageAsset(asset)"
                                    type="button"
                                    class="pf-btn-outline !mr-2 !px-2 !py-1 !text-xs"
                                    @click="openPreview(asset)"
                                >
                                    View
                                </button>
                                <a
                                    :href="resolveAssetDownloadUrl(asset)"
                                    class="pf-btn-outline !mr-2 !px-2 !py-1 !text-xs"
                                >
                                    Download
                                </a>
                                <button
                                    type="button"
                                    class="pf-btn-outline !mr-2 !px-2 !py-1 !text-xs"
                                    @click="copyAssetUrl(asset)"
                                >
                                    {{ copiedAssetId === asset.id ? 'Copied' : 'Copy URL' }}
                                </button>
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

            <div
                v-if="previewAsset"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                @click.self="closePreview"
            >
                <div class="w-full max-w-4xl rounded-lg bg-white p-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="truncate text-sm font-medium text-slate-800" :title="previewAsset.original_name">
                            {{ previewAsset.original_name }}
                        </p>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="pf-btn-outline !px-2 !py-1 !text-xs"
                                @click="copyAssetUrl(previewAsset)"
                            >
                                    {{ copiedAssetId === previewAsset.id ? 'Copied' : 'Copy URL' }}
                            </button>
                            <a :href="resolveAssetDownloadUrl(previewAsset)" class="pf-btn-outline !px-2 !py-1 !text-xs">Download</a>
                            <button type="button" class="pf-btn-outline !px-2 !py-1 !text-xs" @click="closePreview">Close</button>
                        </div>
                    </div>

                    <img
                        :src="resolveAssetPreviewUrl(previewAsset)"
                        :alt="previewAsset.alt_text || previewAsset.original_name"
                        class="max-h-[75vh] w-full rounded object-contain"
                    >
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
