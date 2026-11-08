<script setup lang="ts">
import { computed } from 'vue';
import { Kbd } from '@/components/ui/kbd';
import { Switch } from '@/components/ui/switch';
import type { AspectKey } from '@/lib/analysis';
import { aspects, ethics } from '@/lib/analysis';
import type { Health } from '@/lib/findings';
import { findingOrder } from '@/lib/findings';
import { cn } from '@/lib/utils';
import type { EthicsValue } from '@/types';

const props = defineProps<{
    health: Health | null;
    counts: Record<AspectKey, number>;
    unavailable: AspectKey[];
    ethicsCounts: Record<EthicsValue, number>;
}>();

const enabled = defineModel<Record<AspectKey, boolean>>('enabled', {
    required: true,
});
const showMinor = defineModel<boolean>('showMinor', { required: true });

const underline: Record<AspectKey, 'solid' | 'dotted' | 'dashed'> = {
    fallacy: 'solid',
    rhetoric: 'dotted',
    questions: 'dashed',
    community: 'solid',
};

const ring = computed(() => {
    const circumference = 2 * Math.PI * 28;
    const score = props.health?.score ?? 0;

    return {
        circumference,
        offset: circumference * (1 - score / 100),
        color:
            props.health?.tone === 'good'
                ? 'var(--ethics-neutral)'
                : props.health?.tone === 'fair'
                  ? 'var(--ethics-questionable)'
                  : 'var(--ethics-unethical)',
    };
});

const ethicsTotal = computed(
    () =>
        props.ethicsCounts.neutral +
        props.ethicsCounts.questionable +
        props.ethicsCounts.unethical,
);

const findingsTotal = computed(
    () => props.counts.fallacy + props.counts.rhetoric + props.counts.questions,
);

function toggle(type: AspectKey): void {
    enabled.value = { ...enabled.value, [type]: !enabled.value[type] };
}
</script>

<template>
    <section
        class="grid gap-5 rounded-xl bg-surface p-4 sm:p-5 lg:grid-cols-[auto_minmax(0,1fr)] lg:items-center lg:gap-8"
        data-test="health-summary"
    >
        <div v-if="health" class="flex items-center gap-4">
            <div class="relative size-16 shrink-0">
                <svg viewBox="0 0 66 66" class="size-16 -rotate-90">
                    <circle
                        cx="33"
                        cy="33"
                        r="28"
                        fill="none"
                        stroke-width="7"
                        class="stroke-surface-strong"
                    />
                    <circle
                        cx="33"
                        cy="33"
                        r="28"
                        fill="none"
                        stroke-width="7"
                        stroke-linecap="round"
                        :stroke="ring.color"
                        :stroke-dasharray="ring.circumference"
                        :stroke-dashoffset="ring.offset"
                        class="transition-[stroke-dashoffset] duration-700"
                    />
                </svg>
                <span
                    class="absolute inset-0 grid place-items-center font-display text-xl tabular-nums"
                    >{{ health.score }}</span
                >
            </div>
            <div>
                <p
                    class="text-[10.5px] font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Argument health
                </p>
                <p class="font-display text-lg leading-tight">
                    {{ health.verdict }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ findingsTotal }} findings · {{ health.severe }} high
                    severity
                </p>
            </div>
        </div>

        <div class="grid gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="(type, index) in findingOrder"
                    :key="type"
                    type="button"
                    :disabled="unavailable.includes(type)"
                    :aria-pressed="enabled[type]"
                    :class="
                        cn(
                            'inline-flex h-8 items-center gap-2 rounded-lg px-3 text-[0.8rem] font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40',
                            enabled[type]
                                ? 'text-foreground'
                                : 'bg-transparent text-muted-foreground hover:bg-background/70',
                        )
                    "
                    :style="
                        enabled[type]
                            ? {
                                  backgroundColor: `color-mix(in oklab, ${aspects[type].color} 12%, var(--background))`,
                              }
                            : undefined
                    "
                    :title="`Toggle ${aspects[type].label.toLowerCase()} (${index + 1})`"
                    @click="toggle(type)"
                >
                    <span
                        aria-hidden="true"
                        class="w-4 border-b-2"
                        :style="{
                            borderColor: aspects[type].color,
                            borderBottomStyle: underline[type],
                        }"
                    />
                    {{ aspects[type].label }}
                    <span class="font-mono text-[11px] text-muted-foreground">{{
                        counts[type]
                    }}</span>
                </button>
            </div>

            <div
                class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-muted-foreground"
            >
                <div
                    v-if="ethicsTotal > 0"
                    class="flex items-center gap-2"
                    :title="`${ethicsCounts.neutral} neutral, ${ethicsCounts.questionable} questionable, ${ethicsCounts.unethical} unethical`"
                >
                    <span>Ethics</span>
                    <span
                        class="flex h-1.5 w-36 overflow-hidden rounded-full bg-surface-strong"
                        role="img"
                        :aria-label="`${ethicsCounts.neutral} neutral, ${ethicsCounts.questionable} questionable, ${ethicsCounts.unethical} unethical`"
                    >
                        <span
                            v-for="key in [
                                'neutral',
                                'questionable',
                                'unethical',
                            ] as EthicsValue[]"
                            :key="key"
                            class="h-full"
                            :style="{
                                width: `${(ethicsCounts[key] / ethicsTotal) * 100}%`,
                                backgroundColor: ethics[key].color,
                            }"
                        />
                    </span>
                </div>
                <label class="flex items-center gap-2">
                    <Switch v-model="showMinor" />
                    Show minor findings
                </label>
                <span class="hidden items-center gap-1 lg:ml-auto lg:flex">
                    <Kbd>J</Kbd><Kbd>K</Kbd> step through findings
                </span>
            </div>
        </div>
    </section>
</template>
