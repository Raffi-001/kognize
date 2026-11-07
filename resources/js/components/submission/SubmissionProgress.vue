<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    CircleAlert,
    Globe,
    RotateCcw,
    Type,
} from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show } from '@/routes/submissions';
import type { SubmissionProgress } from '@/types';

const props = defineProps<{
    progress: SubmissionProgress;
}>();

type StepState = 'done' | 'current' | 'waiting' | 'failed';

const order: SubmissionProgress['stage'][] = [
    'queued',
    'fetching',
    'analyzing',
    'done',
];

const steps = computed(() => {
    const all = [
        ...(props.progress.source === 'url'
            ? [
                  {
                      stage: 'fetching' as const,
                      label: 'Fetching the article',
                      hint: 'Reading the page and extracting its text',
                  },
              ]
            : []),
        {
            stage: 'analyzing' as const,
            label: 'Mapping the argument',
            hint: 'Claims, reasons, unstated assumptions and rhetoric',
        },
        {
            stage: 'done' as const,
            label: 'Analysis ready',
            hint: 'Findings and open questions, next to the text',
        },
    ];

    // A failure belongs to the step that was running when it happened.
    const failedAt =
        props.progress.submission_id === null && props.progress.source === 'url'
            ? 'fetching'
            : 'analyzing';
    const current =
        props.progress.stage === 'queued'
            ? 'fetching'
            : props.progress.stage === 'failed'
              ? failedAt
              : props.progress.stage;

    return all.map((step) => {
        let state: StepState = 'waiting';

        if (props.progress.stage === 'done') {
            state = 'done';
        } else if (step.stage === current) {
            state = props.progress.stage === 'failed' ? 'failed' : 'current';
        } else if (order.indexOf(step.stage) < order.indexOf(current)) {
            state = 'done';
        }

        return { ...step, state };
    });
});

const heading = computed(() => {
    switch (props.progress.stage) {
        case 'queued':
            return 'Waiting for a free spot…';
        case 'fetching':
            return 'Fetching the article…';
        case 'analyzing':
            return 'Analyzing…';
        case 'done':
            return 'Your analysis is ready';
        default:
            return 'We could not finish this analysis';
    }
});

const subject = computed(
    () => props.progress.title ?? props.progress.url ?? 'Your text',
);

function startOver(): void {
    router.get(dashboard().url);
}
</script>

<template>
    <section
        class="relative w-full min-w-0 overflow-hidden rounded-2xl bg-surface"
        aria-live="polite"
        data-test="submission-progress"
    >
        <div
            v-if="progress.is_in_progress"
            class="absolute inset-x-0 top-0 h-0.5 overflow-hidden bg-brand-soft"
        >
            <div class="h-full w-2/5 animate-indeterminate bg-brand" />
        </div>

        <div class="grid grid-cols-1 gap-6 p-5 sm:p-8">
            <header class="flex min-w-0 items-start gap-4">
                <div
                    :class="
                        cn(
                            'flex size-11 shrink-0 items-center justify-center rounded-xl shadow-xs',
                            progress.stage === 'done'
                                ? 'bg-brand text-primary-foreground'
                                : progress.stage === 'failed'
                                  ? 'bg-destructive/10 text-destructive'
                                  : 'bg-background text-brand-strong',
                        )
                    "
                >
                    <Check v-if="progress.stage === 'done'" class="size-5" />
                    <CircleAlert
                        v-else-if="progress.stage === 'failed'"
                        class="size-5"
                    />
                    <Globe
                        v-else-if="progress.source === 'url'"
                        class="size-5"
                    />
                    <Type v-else class="size-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-display text-xl sm:text-2xl">
                        {{ heading }}
                    </h2>
                    <p
                        lang="hy"
                        class="mt-0.5 line-clamp-2 text-sm text-pretty text-muted-foreground"
                        :title="subject"
                    >
                        {{ subject }}
                    </p>
                </div>
            </header>

            <ol class="grid gap-1">
                <li
                    v-for="step in steps"
                    :key="step.stage"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors"
                    :class="{ 'bg-background': step.state === 'current' }"
                >
                    <span
                        :class="
                            cn(
                                'flex size-6 shrink-0 items-center justify-center rounded-full',
                                {
                                    'bg-brand text-primary-foreground':
                                        step.state === 'done',
                                    'bg-brand-soft text-brand-strong':
                                        step.state === 'current',
                                    'bg-surface-strong text-muted-foreground':
                                        step.state === 'waiting',
                                    'bg-destructive/10 text-destructive':
                                        step.state === 'failed',
                                },
                            )
                        "
                    >
                        <Check v-if="step.state === 'done'" class="size-3.5" />
                        <Spinner
                            v-else-if="step.state === 'current'"
                            class="size-3.5"
                        />
                        <CircleAlert
                            v-else-if="step.state === 'failed'"
                            class="size-3.5"
                        />
                        <span v-else class="size-1.5 rounded-full bg-current" />
                    </span>
                    <span class="min-w-0">
                        <span
                            class="block text-sm"
                            :class="
                                step.state === 'waiting'
                                    ? 'text-muted-foreground'
                                    : 'font-medium'
                            "
                            >{{ step.label }}</span
                        >
                        <span
                            v-if="step.state === 'current'"
                            class="block text-xs text-muted-foreground"
                            >{{ step.hint }}</span
                        >
                    </span>
                </li>
            </ol>

            <p
                v-if="progress.stage === 'failed' && progress.error"
                class="rounded-xl bg-destructive/8 px-4 py-3 text-sm text-destructive"
            >
                {{ progress.error }}
            </p>

            <footer class="flex flex-wrap items-center justify-between gap-3">
                <p
                    v-if="progress.is_in_progress"
                    class="text-xs text-muted-foreground"
                >
                    Usually under a minute. You can leave this page; we'll keep
                    working.
                </p>
                <span v-else />

                <div
                    class="flex w-full flex-wrap items-center justify-end gap-2 sm:w-auto"
                >
                    <Button
                        v-if="!progress.is_in_progress"
                        variant="ghost"
                        @click="startOver"
                    >
                        <RotateCcw />
                        {{
                            progress.stage === 'failed'
                                ? 'Try again'
                                : 'Analyze another'
                        }}
                    </Button>
                    <Button
                        v-if="progress.submission_id !== null"
                        :variant="
                            progress.stage === 'done' ? 'default' : 'secondary'
                        "
                        :size="progress.stage === 'done' ? 'lg' : 'default'"
                        as-child
                        data-test="view-analysis"
                    >
                        <Link :href="show(progress.submission_id)">
                            {{
                                progress.stage === 'done'
                                    ? 'Open the analysis'
                                    : 'Watch it live'
                            }}
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>
            </footer>
        </div>
    </section>
</template>
