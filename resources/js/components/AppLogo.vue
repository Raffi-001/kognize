<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        size?: 'md' | 'lg';
        class?: HTMLAttributes['class'];
    }>(),
    { size: 'md', class: undefined },
);

const name = usePage().props.name;
</script>

<template>
    <span
        :class="
            cn(
                'font-display leading-none tracking-tight text-brand',
                props.size === 'lg' ? 'text-3xl' : 'text-xl',
                props.class,
            )
        "
    >
        <span class="group-data-[collapsible=icon]:hidden">{{ name }}</span>
        <span
            class="hidden group-data-[collapsible=icon]:inline"
            aria-hidden="true"
            >{{ name.charAt(0) }}</span
        >
    </span>
</template>
