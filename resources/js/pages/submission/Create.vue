<script setup lang="ts">
import { Head, Link, usePoll } from '@inertiajs/vue3';
import { ArrowRight, Inbox } from '@lucide/vue';
import { computed, watch } from 'vue';
import SubmissionComposer from '@/components/submission/SubmissionComposer.vue';
import SubmissionListItem from '@/components/submission/SubmissionListItem.vue';
import SubmissionProgress from '@/components/submission/SubmissionProgress.vue';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import AppLayout from '@/layouts/AppLayout.vue';
import { analysisTypes, aspects } from '@/lib/analysis';
import { dashboard } from '@/routes';
import { index as submissions } from '@/routes/submissions';
import type {
    BreadcrumbItem,
    Submission,
    SubmissionProgress as Progress,
} from '@/types';

defineOptions({
    layout: [
        AppLayout,
        {
            breadcrumbs: [
                {
                    title: 'Analyze text',
                    href: dashboard(),
                },
            ] satisfies BreadcrumbItem[],
        },
    ],
});

const props = defineProps<{
    sampleText: string;
    wordLimits: { min: number; max: number };
    recentSubmissions: Submission[];
    progress: Progress | null;
}>();

const hasWorkInProgress = computed(
    () =>
        props.recentSubmissions.some((item) => item.is_in_progress) ||
        props.progress?.is_in_progress === true,
);

const { start, stop } = usePoll(
    3000,
    { only: ['recentSubmissions', 'progress', 'auth'] },
    { autoStart: false },
);

watch(hasWorkInProgress, (inProgress) => (inProgress ? start() : stop()), {
    immediate: true,
});
</script>

<template>
    <Head title="Analyze text" />

    <div class="relative flex-1">
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(60%_100%_at_50%_0%,color-mix(in_oklab,var(--primary)_22%,transparent),transparent)]"
        />

        <div
            class="relative mx-auto flex w-full max-w-4xl flex-col gap-10 px-4 pt-10 pb-16 sm:px-6 lg:pt-14"
        >
            <section class="text-center">
                <h1
                    class="font-display text-4xl leading-[1.05] tracking-tight text-balance sm:text-5xl"
                >
                    Read between the
                    <em class="text-brand-strong not-italic">lines</em>.
                </h1>
                <p
                    class="mx-auto mt-4 max-w-xl text-base text-pretty text-muted-foreground"
                >
                    Paste Armenian commentary or an article link. We'll map its
                    logical fallacies, rhetorical devices and the questions
                    worth asking.
                </p>
                <div
                    class="mt-5 flex flex-wrap items-center justify-center gap-2"
                >
                    <span
                        v-for="type in analysisTypes"
                        :key="type"
                        class="inline-flex items-center gap-1.5 rounded-full bg-surface px-3 py-1 text-xs font-medium"
                    >
                        <component
                            :is="aspects[type].icon"
                            class="size-3.5"
                            :class="aspects[type].textClass"
                        />
                        {{ aspects[type].label }}
                    </span>
                </div>
            </section>

            <SubmissionProgress v-if="progress" :progress="progress" />
            <SubmissionComposer
                v-else
                :sample-text="sampleText"
                :word-limits="wordLimits"
            />

            <section class="grid gap-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-medium text-muted-foreground">
                        Recent analyses
                    </h2>
                    <Link
                        v-if="recentSubmissions.length > 0"
                        :href="submissions()"
                        class="flex items-center gap-1 text-sm font-medium text-brand-strong hover:underline"
                    >
                        View all <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <div
                    v-if="recentSubmissions.length > 0"
                    class="grid grid-cols-1 gap-0.5 rounded-2xl bg-surface p-1.5"
                >
                    <SubmissionListItem
                        v-for="submission in recentSubmissions"
                        :key="submission.id"
                        :submission="submission"
                    />
                </div>

                <Empty v-else class="rounded-2xl bg-surface">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Inbox />
                        </EmptyMedia>
                        <EmptyTitle>No analyses yet</EmptyTitle>
                        <EmptyDescription>
                            Your first analysis will appear here. Not sure where
                            to start? Try the sample text above.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </section>
        </div>
    </div>
</template>
