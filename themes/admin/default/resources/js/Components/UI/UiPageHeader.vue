<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="pf-section-title">{{ title }}</h1>
                <p v-if="subtitle" class="pf-section-subtitle">{{ subtitle }}</p>
            </div>
            <slot name="actions"/>
        </div>
        <slot />
    </div>
</template>
