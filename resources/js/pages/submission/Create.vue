<script setup lang="ts">
import { Head, Link, usePoll } from '@inertiajs/vue3';
import { ArrowRight, Inbox } from '@lucide/vue';
import { computed, watch } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
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

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6">
        <PageHeader
            title="Analyze a text"
            description="Paste an article link or an Armenian text. We map its argument and show the reasoning gaps, rhetoric and questions worth asking."
        />

        <div class="grid min-w-0 gap-8">
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
                    class="grid grid-cols-1 divide-y border-y"
                >
                    <SubmissionListItem
                        v-for="submission in recentSubmissions"
                        :key="submission.id"
                        :submission="submission"
                    />
                </div>

                <Empty v-else class="rounded-xl border border-dashed">
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
