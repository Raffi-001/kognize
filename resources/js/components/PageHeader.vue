<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <header class="grid gap-4">
        <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
            <div class="min-w-0 flex-1">
                <h1 class="font-display text-2xl leading-tight tracking-tight">
                    {{ title }}
                </h1>
                <p
                    v-if="description || $slots.description"
                    class="mt-1 max-w-2xl text-sm text-pretty text-muted-foreground"
                >
                    <slot name="description">{{ description }}</slot>
                </p>
            </div>
            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <slot />
    </header>
</template>
