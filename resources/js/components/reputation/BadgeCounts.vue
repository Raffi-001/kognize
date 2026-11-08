<script setup lang="ts">
import { badgeTiers, tierOrder } from '@/lib/reputation';
import type { BadgeTierValue } from '@/lib/reputation';

defineProps<{
    counts: Record<BadgeTierValue, number>;
}>();
</script>

<template>
    <span class="inline-flex min-h-4 items-center gap-2.5 text-xs tabular-nums">
        <template v-for="tier in tierOrder" :key="tier">
            <span
                v-if="counts[tier] > 0"
                class="inline-flex items-center gap-1"
                :title="`${counts[tier]} ${badgeTiers[tier].label.toLowerCase()} ${counts[tier] === 1 ? 'badge' : 'badges'}`"
            >
                <span
                    class="size-2 rounded-full"
                    :class="badgeTiers[tier].dot"
                />
                {{ counts[tier] }}
            </span>
        </template>
    </span>
</template>
