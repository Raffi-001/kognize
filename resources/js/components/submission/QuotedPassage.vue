<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineProps<{
    color: string;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <blockquote
        lang="hy"
        :class="
            cn(
                'relative pl-3.5 text-sm text-muted-foreground italic',
                $props.class,
            )
        "
    >
        <span
            aria-hidden="true"
            class="absolute inset-y-0.5 left-0 w-[3px] rounded-full"
            :style="{ backgroundColor: color }"
        />
        <slot />
    </blockquote>
</template>
