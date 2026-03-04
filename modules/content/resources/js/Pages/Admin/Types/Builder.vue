<script setup>
import { ref } from 'vue';

const props = defineProps({
    initialFields: {
        type: Array,
        default: () => [],
    },
    fieldTypes: {
        type: Array,
        default: () => [],
    },
});

const fields = ref(props.initialFields.length > 0 ? props.initialFields : [
    {
        key: 'title',
        label: 'Title',
        field_type: 'text',
        config: {},
        validation: {},
        conditional: {},
        sort_order: 0,
        is_required: true,
        is_localized: false,
    },
]);

const draggingIndex = ref(null);

const addField = () => {
    fields.value.push({
        key: 'field_' + (fields.value.length + 1),
        label: 'Field ' + (fields.value.length + 1),
        field_type: 'text',
        config: {},
        validation: {},
        conditional: {},
        sort_order: fields.value.length,
        is_required: false,
        is_localized: false,
    });
};

const removeField = (index) => {
    fields.value.splice(index, 1);
};

const startDrag = (index) => {
    draggingIndex.value = index;
};

const dropOn = (index) => {
    if (draggingIndex.value === null || draggingIndex.value === index) {
        return;
    }

    const [moved] = fields.value.splice(draggingIndex.value, 1);
    fields.value.splice(index, 0, moved);
    draggingIndex.value = null;
};
</script>

<template>
    <section>
        <h2>Schema builder</h2>
        <button type="button" @click="addField">Add field</button>

        <div
            v-for="(field, index) in fields"
            :key="index"
            draggable="true"
            @dragstart="startDrag(index)"
            @dragover.prevent
            @drop="dropOn(index)"
            style="border:1px solid #ccc; padding:12px; margin-top:8px;"
        >
            <strong>Field {{ index + 1 }}</strong>
            <label>
                Key
                <input v-model="field.key" :name="`fields[${index}][key]`" required>
            </label>
            <label>
                Label
                <input v-model="field.label" :name="`fields[${index}][label]`" required>
            </label>
            <label>
                Type
                <select v-model="field.field_type" :name="`fields[${index}][field_type]`">
                    <option v-for="type in fieldTypes" :key="type" :value="type">{{ type }}</option>
                </select>
            </label>
            <label>
                Config JSON
                <textarea :name="`fields[${index}][config]`">{{ JSON.stringify(field.config ?? {}) }}</textarea>
            </label>
            <label>
                Validation JSON
                <textarea :name="`fields[${index}][validation]`">{{ JSON.stringify(field.validation ?? {}) }}</textarea>
            </label>
            <label>
                Conditional JSON
                <textarea :name="`fields[${index}][conditional]`">{{ JSON.stringify(field.conditional ?? {}) }}</textarea>
            </label>

            <input type="hidden" :name="`fields[${index}][sort_order]`" :value="index">

            <label>
                Required
                <input type="checkbox" :name="`fields[${index}][is_required]`" value="1" :checked="field.is_required">
            </label>
            <label>
                Localized
                <input type="checkbox" :name="`fields[${index}][is_localized]`" value="1" :checked="field.is_localized">
            </label>

            <button type="button" @click="removeField(index)">Remove</button>
        </div>
    </section>
</template>
