<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CircleHelp, FileText, MessageCircle, Paperclip } from '@lucide/vue';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show } from '@/routes/questions';
import type {
    BreadcrumbItem,
    Paginated,
    QuestionStatusValue,
    QuestionSummary,
} from '@/types';

defineOptions({
    layout: [
        AppLayout,
        {
            breadcrumbs: [
                { title: 'Unresolved issues', href: index() },
            ] satisfies BreadcrumbItem[],
        },
    ],
});

const props = defineProps<{
    questions: Paginated<QuestionSummary>;
    status: QuestionStatusValue;
    counts: Record<QuestionStatusValue, number>;
}>();

function visit(status: QuestionStatusValue, page?: number): void {
    router.get(
        index({
            query: {
                status: status === 'open' ? undefined : status,
                page: page && page > 1 ? page : undefined,
            },
        }).url,
        {},
        { preserveState: true, replace: true },
    );
}

function changeStatus(value: unknown): void {
    if (value === 'open' || value === 'resolved') {
        visit(value);
    }
}
</script>

<template>
    <Head title="Unresolved issues" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 px-4 py-8 sm:px-6">
        <header class="flex flex-col gap-1">
            <h1 class="font-display text-4xl tracking-tight">
                Unresolved issues
            </h1>
            <p class="text-muted-foreground">
                Factual claims the analyses could not settle on their own. Cite
                a source on the page of an issue; moderators resolve it once
                verified evidence points one way.
            </p>
        </header>

        <ToggleGroup
            type="single"
            :model-value="props.status"
            class="self-start rounded-lg bg-surface p-0.5"
            @update:model-value="changeStatus"
        >
            <ToggleGroupItem
                value="open"
                class="gap-1.5 px-4 data-[state=on]:bg-background data-[state=on]:shadow-xs"
                >Unresolved
                <span class="text-xs text-muted-foreground tabular-nums">{{
                    counts.open
                }}</span></ToggleGroupItem
            >
            <ToggleGroupItem
                value="resolved"
                class="gap-1.5 px-4 data-[state=on]:bg-background data-[state=on]:shadow-xs"
                >Resolved
                <span class="text-xs text-muted-foreground tabular-nums">{{
                    counts.resolved
                }}</span></ToggleGroupItem
            >
        </ToggleGroup>

        <ol
            v-if="questions.data.length > 0"
            class="grid gap-0.5 rounded-2xl bg-surface p-1.5"
        >
            <li v-for="question in questions.data" :key="question.id">
                <Link
                    :href="show(question.id)"
                    class="group flex gap-4 rounded-xl px-3 py-3 transition-colors hover:bg-background sm:px-4"
                    data-test="question-list-item"
                >
                    <div
                        class="hidden w-16 shrink-0 flex-col items-end gap-1 pt-0.5 text-xs text-muted-foreground tabular-nums sm:flex"
                    >
                        <span
                            class="flex items-center gap-1"
                            :class="{
                                'font-medium text-foreground':
                                    question.evidence_count > 0,
                            }"
                            :title="`${question.evidence_count} sources cited`"
                        >
                            {{ question.evidence_count }}
                            <Paperclip class="size-3" />
                        </span>
                        <span
                            class="flex items-center gap-1"
                            :title="`${question.comments_count} replies`"
                        >
                            {{ question.comments_count }}
                            <MessageCircle class="size-3" />
                        </span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p
                            lang="hy"
                            class="text-[0.95rem] leading-snug font-medium text-pretty group-hover:text-brand-strong"
                        >
                            {{ question.question }}
                        </p>
                        <p
                            v-if="question.passage"
                            lang="hy"
                            class="mt-1 line-clamp-1 text-sm text-muted-foreground italic"
                        >
                            “{{ question.passage }}”
                        </p>
                        <p
                            class="mt-1.5 flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground"
                        >
                            <span
                                v-if="question.verdict_label"
                                class="rounded-full bg-brand-soft px-1.5 py-px font-medium text-brand-strong"
                                >{{ question.verdict_label }}</span
                            >
                            <FileText class="size-3 shrink-0" />
                            <span lang="hy" class="max-w-80 truncate">{{
                                question.source.title
                            }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{
                                question.resolved_at_human
                                    ? `resolved ${question.resolved_at_human}`
                                    : `raised ${question.created_at_human}`
                            }}</span>
                        </p>
                    </div>
                </Link>
            </li>
        </ol>

        <Empty v-else class="rounded-2xl bg-surface">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <CircleHelp />
                </EmptyMedia>
                <EmptyTitle>{{
                    status === 'open'
                        ? 'No unresolved issues'
                        : 'Nothing resolved yet'
                }}</EmptyTitle>
                <EmptyDescription>
                    {{
                        status === 'open'
                            ? 'When an analysis finds a claim that needs evidence, it appears here.'
                            : 'Issues appear here once a moderator settles them.'
                    }}
                </EmptyDescription>
            </EmptyHeader>
        </Empty>

        <div
            v-if="questions.meta.last_page > 1"
            class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between"
        >
            <p class="text-sm text-muted-foreground tabular-nums">
                Showing {{ questions.meta.from }}–{{ questions.meta.to }} of
                {{ questions.meta.total }}
            </p>
            <Pagination
                v-slot="{ page }"
                :total="questions.meta.total"
                :items-per-page="questions.meta.per_page"
                :page="questions.meta.current_page"
                :sibling-count="1"
                class="mx-0 w-auto"
                @update:page="(page: number) => visit(status, page)"
            >
                <PaginationContent v-slot="{ items }">
                    <PaginationPrevious />
                    <template v-for="(item, position) in items" :key="position">
                        <PaginationItem
                            v-if="item.type === 'page'"
                            :value="item.value"
                            :is-active="item.value === page"
                        >
                            {{ item.value }}
                        </PaginationItem>
                        <PaginationEllipsis v-else :index="position" />
                    </template>
                    <PaginationNext />
                </PaginationContent>
            </Pagination>
        </div>
    </div>
</template>
