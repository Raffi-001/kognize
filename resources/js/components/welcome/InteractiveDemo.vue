<script setup lang="ts">
import { ChevronLeft, ChevronRight, MousePointerClick } from '@lucide/vue';
import { useIntervalFn, usePreferredReducedMotion } from '@vueuse/core';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { aspects } from '@/lib/analysis';
import type { AnalysisTypeValue } from '@/types';

type DemoFinding = {
    type: AnalysisTypeValue;
    tag: string;
    title: string;
    text: string;
    strength: number | null;
};

/** A made-up coffee shop ad, so the demo stays clear of real people and politics. */
const findings: DemoFinding[] = [
    {
        type: 'fallacy',
        tag: 'Reasoning gap',
        title: 'No true Scotsman',
        text: 'Anyone who dislikes the coffee is ruled out as a "real" coffee drinker, so the claim can never be proven wrong.',
        strength: 7,
    },
    {
        type: 'questions',
        tag: 'Open question',
        title: 'Which baristas, and who measured "freshest"?',
        text: 'An appeal to common knowledge with no source behind it. Readers can cite evidence to settle it.',
        strength: null,
    },
    {
        type: 'rhetoric',
        tag: 'Rhetoric',
        title: 'Manufactured urgency',
        text: 'A forced either/or that pushes you to act now instead of weighing the offer.',
        strength: 6,
    },
];

const parts: { text: string; finding: number | null }[] = [
    { text: 'Եթե մեր սուրճը քեզ դուր չի գալիս, ', finding: null },
    { text: 'ուրեմն դու երբեք իսկական սուրճ չես խմել', finding: 0 },
    { text: '։ ', finding: null },
    { text: 'Բոլոր բարիստաները գիտեն', finding: 1 },
    { text: ', որ մեր հատիկները քաղաքում ամենաթարմն են։ ', finding: null },
    { text: 'Կամ այսօր կփորձես, կամ ընդմիշտ կզղջաս', finding: 2 },
    { text: '։', finding: null },
];

const active = ref(0);
const finding = computed(() => findings[active.value]);
const aspect = computed(() => aspects[finding.value.type]);

const reducedMotion = usePreferredReducedMotion();
const { pause } = useIntervalFn(
    () => {
        active.value = (active.value + 1) % findings.length;
    },
    4500,
    { immediate: reducedMotion.value !== 'reduce' },
);

function select(index: number): void {
    pause();
    active.value = (index + findings.length) % findings.length;
}
</script>

<template>
    <section
        class="mx-auto mt-16 max-w-4xl overflow-hidden rounded-xl border bg-card text-left shadow-[0_24px_48px_-24px_rgba(0,0,0,0.18)]"
        aria-label="Interactive example analysis"
        data-test="welcome-demo"
    >
        <div class="flex items-center gap-3 border-b bg-surface px-4 py-2.5">
            <span class="flex gap-1.5" aria-hidden="true">
                <span
                    v-for="dot in 3"
                    :key="dot"
                    class="size-2.5 rounded-full bg-muted-foreground/20"
                />
            </span>
            <span
                class="mx-auto truncate rounded-md bg-background px-3 py-1 text-xs text-muted-foreground"
                >kognize.me/analyses/coffee-ad</span
            >
        </div>

        <div class="grid md:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="p-6 md:p-8">
                <p
                    class="mb-3 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                >
                    Example · a fictional coffee shop ad
                </p>
                <p lang="hy" class="reading-prose">
                    <template v-for="(part, index) in parts" :key="index">
                        <mark
                            v-if="part.finding !== null"
                            role="button"
                            tabindex="0"
                            class="finding-mark [box-decoration-break:clone] focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
                            :data-type="findings[part.finding].type"
                            :data-active="active === part.finding"
                            :aria-pressed="active === part.finding"
                            :data-test="`demo-mark-${findings[part.finding].type}`"
                            @click="select(part.finding)"
                            @keydown.enter.prevent="select(part.finding)"
                            @keydown.space.prevent="select(part.finding)"
                            >{{ part.text }}</mark
                        >
                        <template v-else>{{ part.text }}</template>
                    </template>
                </p>
                <p
                    class="mt-6 flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <MousePointerClick class="size-3.5" />
                    Click a highlight to see why it was flagged.
                </p>
            </div>

            <aside
                class="flex flex-col border-t bg-surface/60 p-5 md:border-t-0 md:border-l"
                aria-live="polite"
            >
                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-1 opacity-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-to-class="opacity-0"
                >
                    <article
                        :key="active"
                        class="relative flex-1 rounded-lg bg-background p-4 shadow-sm ring-1 ring-foreground/5"
                        data-test="welcome-demo-note"
                    >
                        <span
                            aria-hidden="true"
                            class="absolute inset-y-4 left-0 w-[3px] rounded-full"
                            :style="{ backgroundColor: aspect.color }"
                        />
                        <header class="flex items-center gap-2">
                            <component
                                :is="aspect.icon"
                                class="size-3.5"
                                :class="aspect.textClass"
                            />
                            <span
                                class="text-[10.5px] font-semibold tracking-wider uppercase"
                                :class="aspect.textClass"
                                >{{ finding.tag }}</span
                            >
                            <span
                                v-if="finding.strength !== null"
                                class="ml-auto flex items-center gap-0.5"
                                :title="`Strength ${finding.strength}/10`"
                            >
                                <span
                                    v-for="step in 10"
                                    :key="step"
                                    class="h-2.5 w-1 rounded-full"
                                    :class="
                                        step <= finding.strength
                                            ? ''
                                            : 'bg-surface-strong'
                                    "
                                    :style="
                                        step <= finding.strength
                                            ? { backgroundColor: aspect.color }
                                            : undefined
                                    "
                                />
                            </span>
                            <span
                                v-else
                                class="ml-auto rounded-full bg-questions-soft px-1.5 py-px text-[10px] font-medium text-questions"
                                >Open</span
                            >
                        </header>
                        <h3 class="mt-2 text-sm leading-snug font-semibold">
                            {{ finding.title }}
                        </h3>
                        <p
                            class="mt-1.5 text-[0.8rem] leading-relaxed text-foreground/80"
                        >
                            {{ finding.text }}
                        </p>
                    </article>
                </Transition>

                <div class="mt-3 flex items-center justify-between">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Previous finding"
                        @click="select(active - 1)"
                    >
                        <ChevronLeft />
                    </Button>
                    <span class="flex gap-1.5">
                        <button
                            v-for="(item, index) in findings"
                            :key="index"
                            type="button"
                            class="size-2 rounded-full transition-colors"
                            :style="{
                                backgroundColor:
                                    index === active
                                        ? aspects[item.type].color
                                        : undefined,
                            }"
                            :class="
                                index === active ? '' : 'bg-muted-foreground/25'
                            "
                            :aria-label="`Show ${item.tag}`"
                            @click="select(index)"
                        />
                    </span>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Next finding"
                        @click="select(active + 1)"
                    >
                        <ChevronRight />
                    </Button>
                </div>
            </aside>
        </div>
    </section>
</template>
