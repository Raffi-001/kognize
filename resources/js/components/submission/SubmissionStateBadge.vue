<script setup lang="ts">
import { computed } from 'vue';
import { states } from '@/lib/analysis';
import { cn } from '@/lib/utils';
import type { SubmissionStateValue } from '@/types';

const props = defineProps<{
    state: SubmissionStateValue;
    label: string;
    class?: string;
}>();

const meta = computed(() => states[props.state]);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                meta.class,
                props.class,
            )
        "
    >
        <component
            :is="meta.icon"
            class="size-3.5"
            :class="{ 'animate-spin': meta.spin }"
        />
        {{ label }}
    </span>
</template>
