<script setup lang="ts">
import { Head, Link, setLayoutProps, usePoll } from '@inertiajs/vue3';
import { ArrowUpRight, CircleHelp, Flag } from '@lucide/vue';
import { computed, watch } from 'vue';
import ReportDialog from '@/components/ReportDialog.vue';
import CommentThread from '@/components/submission/CommentThread.vue';
import QuestionPanel from '@/components/submission/QuestionPanel.vue';
import QuotedPassage from '@/components/submission/QuotedPassage.vue';
import { Button } from '@/components/ui/button';
import { provideReportDialog } from '@/composables/useReportDialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { questionOf } from '@/lib/findings';
import { store as storeComment } from '@/routes/analysis-notes/comments';
import { index, show } from '@/routes/questions';
import { show as showSubmission } from '@/routes/submissions';
import type { AnalysisNote, BreadcrumbItem, SubmissionSource } from '@/types';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    question: AnalysisNote;
    source: SubmissionSource;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Unresolved issues', href: index() },
        { title: `#${props.question.id}`, href: show(props.question.id) },
    ] satisfies BreadcrumbItem[],
});

const state = computed(() => questionOf(props.question));
const title = computed(
    () => props.question.aspect_label ?? props.question.text,
);

const { open, reportableType, reportableId, report } = provideReportDialog();

/* Evidence is checked in the background; refresh until every source is. */
const { start, stop } = usePoll(
    2500,
    { only: ['question'] },
    { autoStart: false },
);

watch(
    () =>
        (props.question.evidence ?? []).some(
            (evidence) => evidence.verification === 'pending',
        ),
    (pending) => (pending ? start() : stop()),
    { immediate: true },
);
</script>

<template>
    <Head :title="title" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6">
        <div class="flex max-w-3xl flex-col gap-6">
            <header class="flex flex-col gap-2">
                <p
                    class="flex items-center gap-2 text-[10.5px] font-semibold tracking-wider text-questions uppercase"
                >
                    <CircleHelp class="size-3.5" />
                    Factual question
                    <span
                        class="rounded-full px-1.5 py-px text-[10px] tracking-normal normal-case"
                        :class="
                            question.status === 'resolved'
                                ? 'bg-brand-soft text-brand-strong'
                                : 'bg-questions-soft text-questions'
                        "
                        >{{
                            question.status === 'resolved' ? 'Resolved' : 'Open'
                        }}</span
                    >
                </p>
                <h1
                    lang="hy"
                    class="font-display text-2xl leading-snug text-pretty"
                >
                    {{ title }}
                </h1>
                <Link
                    :href="showSubmission(source.id)"
                    class="inline-flex items-center gap-1 self-start text-sm text-muted-foreground hover:text-foreground"
                >
                    Raised by
                    <span lang="hy" class="font-medium text-foreground">{{
                        source.title
                    }}</span>
                    <span v-if="source.source_host"
                        >· {{ source.source_host }}</span
                    >
                    <ArrowUpRight class="size-3.5" />
                </Link>
            </header>

            <section class="grid gap-3 rounded-xl bg-surface p-4 sm:p-5">
                <QuotedPassage v-if="question.label" color="var(--questions)">
                    {{ question.label }}
                </QuotedPassage>
                <p lang="hy" class="text-sm leading-relaxed text-foreground/85">
                    {{ question.text }}
                </p>
                <QuestionPanel
                    v-if="state"
                    class="mt-0"
                    :question-id="question.id"
                    :question="state"
                />
            </section>

            <section class="grid gap-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold">
                        Discussion
                        <span class="font-normal text-muted-foreground">{{
                            question.comments.length
                        }}</span>
                    </h2>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-7 text-xs text-muted-foreground hover:text-destructive"
                        @click="report('analysis_note', question.id)"
                    >
                        <Flag />
                        Report
                    </Button>
                </div>
                <CommentThread
                    :comments="question.comments"
                    :action="storeComment.form(question.id)"
                />
            </section>
        </div>
    </div>

    <ReportDialog
        v-model:open="open"
        :reportable-type="reportableType"
        :reportable-id="reportableId"
    />
</template>
