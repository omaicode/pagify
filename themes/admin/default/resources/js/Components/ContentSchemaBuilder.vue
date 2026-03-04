<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    fieldTypes: {
        type: Array,
        default: () => [],
    },
    relationTypes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);
const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});

const draggedFieldIndex = ref(null);
const draggedPaletteType = ref('');
const selectOptionDrafts = ref({});

const fields = computed({
    get: () => props.modelValue ?? [],
    set: (nextValue) => emit('update:modelValue', nextValue),
});

const normalizeField = (field, index = 0) => ({
    key: field?.key ?? `field_${index + 1}`,
    label: field?.label ?? `Field ${index + 1}`,
    field_type: field?.field_type ?? props.fieldTypes[0] ?? 'text',
    config: { ...(field?.config ?? {}) },
    validation: { ...(field?.validation ?? {}) },
    conditional: { ...(field?.conditional ?? {}) },
    sort_order: field?.sort_order ?? index,
    is_required: !!field?.is_required,
    is_localized: !!field?.is_localized,
});

const updateFields = (nextFields) => {
    fields.value = nextFields.map((item, index) => ({
        ...normalizeField(item, index),
        sort_order: index,
    }));
};

const addField = (fieldType = props.fieldTypes[0] ?? 'text') => {
    const next = [...fields.value, normalizeField({ field_type: fieldType }, fields.value.length)];
    updateFields(next);
};

const removeField = (index) => {
    const next = fields.value.filter((_, itemIndex) => itemIndex !== index);

    if (next.length === 0) {
        updateFields([normalizeField({ key: 'title', label: 'Title', field_type: 'text', is_required: true }, 0)]);
        return;
    }

    updateFields(next);
};

const moveField = (fromIndex, toIndex) => {
    if (toIndex < 0 || toIndex >= fields.value.length || fromIndex === toIndex) {
        return;
    }

    const next = [...fields.value];
    const [item] = next.splice(fromIndex, 1);
    next.splice(toIndex, 0, item);
    updateFields(next);
};

const onFieldDragStart = (index) => {
    draggedFieldIndex.value = index;
};

const onFieldDrop = (index) => {
    if (draggedFieldIndex.value === null) {
        return;
    }

    moveField(draggedFieldIndex.value, index);
    draggedFieldIndex.value = null;
};

const onPaletteDragStart = (type) => {
    draggedPaletteType.value = type;
};

const onCanvasDrop = () => {
    if (!draggedPaletteType.value) {
        return;
    }

    addField(draggedPaletteType.value);
    draggedPaletteType.value = '';
};

const ensureFieldConfig = (field) => {
    field.config = field.config ?? {};
    field.validation = field.validation ?? {};
    field.conditional = field.conditional ?? {};
};

const addSelectOption = (field, index) => {
    ensureFieldConfig(field);

    const draft = (selectOptionDrafts.value[index] ?? '').trim();
    if (draft === '') {
        return;
    }

    const currentOptions = Array.isArray(field.config.options) ? field.config.options : [];
    field.config.options = [...currentOptions, draft];
    selectOptionDrafts.value[index] = '';
};

const removeSelectOption = (field, optionIndex) => {
    ensureFieldConfig(field);
    const currentOptions = Array.isArray(field.config.options) ? field.config.options : [];
    field.config.options = currentOptions.filter((_, index) => index !== optionIndex);
};
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-12">
        <aside class="space-y-2 rounded border border-slate-200 bg-white p-3 lg:col-span-3">
            <h3 class="text-sm font-semibold text-slate-900">{{ t.field_palette ?? 'Field palette' }}</h3>
            <p class="text-xs text-slate-500">{{ t.drag_type_to_canvas ?? 'Drag a type into canvas or click Add.' }}</p>

            <div class="grid grid-cols-2 gap-2 lg:grid-cols-1">
                <button
                    v-for="type in fieldTypes"
                    :key="type"
                    type="button"
                    draggable="true"
                    class="rounded border border-slate-300 px-2 py-1 text-left text-xs text-slate-700"
                    @dragstart="onPaletteDragStart(type)"
                    @click="addField(type)"
                >
                    {{ t.add ?? 'Add' }} {{ type }}
                </button>
            </div>
        </aside>

        <section
            class="space-y-3 rounded border border-slate-200 bg-white p-3 lg:col-span-9"
            @dragover.prevent
            @drop.prevent="onCanvasDrop"
        >
            <h3 class="text-sm font-semibold text-slate-900">{{ t.schema_canvas ?? 'Schema canvas' }}</h3>
            <p class="text-xs text-slate-500">{{ t.drag_drop_reorder_fields ?? 'Drag and drop fields to reorder.' }}</p>

            <article
                v-for="(field, index) in fields"
                :key="`${field.key}-${index}`"
                draggable="true"
                class="rounded border border-slate-200 p-3"
                @dragstart="onFieldDragStart(index)"
                @dragover.prevent
                @drop.prevent="onFieldDrop(index)"
            >
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <p class="text-sm font-semibold text-slate-900">{{ t.field_number ?? 'Field' }} #{{ index + 1 }}</p>
                    <button type="button" class="rounded border border-slate-300 px-2 py-0.5 text-xs" :disabled="index === 0" @click="moveField(index, index - 1)">{{ t.up ?? 'Up' }}</button>
                    <button type="button" class="rounded border border-slate-300 px-2 py-0.5 text-xs" :disabled="index === fields.length - 1" @click="moveField(index, index + 1)">{{ t.down ?? 'Down' }}</button>
                    <button type="button" class="rounded border border-rose-300 px-2 py-0.5 text-xs text-rose-700" @click="removeField(index)">{{ t.remove ?? 'Remove' }}</button>
                </div>

                <div class="grid gap-2 md:grid-cols-2">
                    <label class="text-sm">{{ t.key ?? 'Key' }}<input v-model="field.key" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required></label>
                    <label class="text-sm">{{ t.label ?? 'Label' }}<input v-model="field.label" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required></label>
                    <label class="text-sm md:col-span-2">{{ t.type ?? 'Type' }}
                        <select v-model="field.field_type" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" @change="ensureFieldConfig(field)">
                            <option v-for="type in fieldTypes" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </label>
                </div>

                <div class="mt-2 flex flex-wrap gap-3">
                    <label class="inline-flex items-center gap-2 text-sm"><input v-model="field.is_required" type="checkbox">{{ t.required ?? 'Required' }}</label>
                    <label class="inline-flex items-center gap-2 text-sm"><input v-model="field.is_localized" type="checkbox">{{ t.localized ?? 'Localized' }}</label>
                </div>

                <div class="mt-3 space-y-2 rounded bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ t.field_options ?? 'Field options' }}</p>

                    <template v-if="field.field_type === 'select'">
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(option, optionIndex) in (Array.isArray(field.config?.options) ? field.config.options : [])"
                                :key="`${option}-${optionIndex}`"
                                class="inline-flex items-center gap-1 rounded bg-white px-2 py-1 text-xs text-slate-700"
                            >
                                {{ option }}
                                <button type="button" class="text-rose-700" @click="removeSelectOption(field, optionIndex)">×</button>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <input v-model="selectOptionDrafts[index]" type="text" class="w-full rounded border border-slate-300 px-2 py-1 text-sm" :placeholder="t.new_option ?? 'New option'">
                            <button type="button" class="rounded border border-slate-300 px-2 py-1 text-xs" @click="addSelectOption(field, index)">{{ t.add ?? 'Add' }}</button>
                        </div>
                    </template>

                    <template v-if="field.field_type === 'relation'">
                        <label class="block text-sm">{{ t.relation_type ?? 'Relation type' }}
                            <select v-model="field.config.relation_type" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                                <option v-for="type in relationTypes" :key="type" :value="type">{{ type }}</option>
                            </select>
                        </label>
                        <label class="block text-sm">{{ t.target_content_type_slug ?? 'Target content type slug' }}
                            <input v-model="field.config.target_content_type_slug" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                        </label>
                    </template>

                    <template v-if="field.field_type === 'number'">
                        <div class="grid gap-2 md:grid-cols-3">
                            <label class="text-sm">{{ t.min ?? 'Min' }}<input v-model.number="field.validation.min" type="number" class="mt-1 w-full rounded border border-slate-300 px-2 py-1"></label>
                            <label class="text-sm">{{ t.max ?? 'Max' }}<input v-model.number="field.validation.max" type="number" class="mt-1 w-full rounded border border-slate-300 px-2 py-1"></label>
                            <label class="text-sm">{{ t.step ?? 'Step' }}<input v-model.number="field.validation.step" type="number" class="mt-1 w-full rounded border border-slate-300 px-2 py-1"></label>
                        </div>
                    </template>

                    <template v-if="field.field_type === 'repeater'">
                        <div class="grid gap-2 md:grid-cols-2">
                            <label class="text-sm">{{ t.min_items ?? 'Min items' }}<input v-model.number="field.config.min_items" type="number" min="0" class="mt-1 w-full rounded border border-slate-300 px-2 py-1"></label>
                            <label class="text-sm">{{ t.max_items ?? 'Max items' }}<input v-model.number="field.config.max_items" type="number" min="0" class="mt-1 w-full rounded border border-slate-300 px-2 py-1"></label>
                        </div>
                    </template>

                    <template v-if="field.field_type === 'conditional'">
                        <div class="grid gap-2 md:grid-cols-3">
                            <label class="text-sm">{{ t.depends_on_key ?? 'Depends on key' }}<input v-model="field.conditional.depends_on" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1"></label>
                            <label class="text-sm">{{ t.operator ?? 'Operator' }}
                                <select v-model="field.conditional.operator" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                                    <option value="eq">{{ t.equals ?? 'equals' }}</option>
                                    <option value="neq">{{ t.not_equals ?? 'not equals' }}</option>
                                    <option value="in">in</option>
                                    <option value="nin">{{ t.not_in ?? 'not in' }}</option>
                                </select>
                            </label>
                            <label class="text-sm">{{ t.value ?? 'Value' }}<input v-model="field.conditional.value" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1"></label>
                        </div>
                    </template>
                </div>
            </article>

            <button type="button" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700" @click="addField('text')">
                {{ t.add_text_field ?? 'Add text field' }}
            </button>
        </section>
    </div>
</template>
