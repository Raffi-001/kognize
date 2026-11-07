<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { Inbox, Search, SearchX } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive } from 'vue';
import SubmissionListItem from '@/components/submission/SubmissionListItem.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index } from '@/routes/submissions';
import type {
    BreadcrumbItem,
    Paginated,
    Submission,
    SubmissionStateValue,
} from '@/types';

type Scope = 'mine' | 'community';

const props = defineProps<{
    submissions: Paginated<Submission>;
    filters: {
        scope: Scope;
        state: SubmissionStateValue | null;
        search: string | null;
    };
    states: { value: SubmissionStateValue; label: string }[];
}>();

defineOptions({
    layout: AppLayout,
});

const isCommunity = computed(() => props.filters.scope === 'community');

setLayoutProps({
    breadcrumbs: [
        {
            title: isCommunity.value ? 'Community feed' : 'My analyses',
            href: isCommunity.value
                ? index({ query: { scope: 'community' } })
                : index(),
        },
    ] satisfies BreadcrumbItem[],
});

const form = reactive({
    scope: props.filters.scope,
    state: props.filters.state ?? 'all',
    search: props.filters.search ?? '',
});

const hasFilters = computed(
    () => form.state !== 'all' || form.search.trim() !== '',
);

function visit(page?: number): void {
    router.get(
        index({
            query: {
                scope: form.scope === 'mine' ? undefined : form.scope,
                state: form.state === 'all' ? undefined : form.state,
                search: form.search.trim() || undefined,
                page: page && page > 1 ? page : undefined,
            },
        }).url,
        {},
        {
            preserveState: true,
            preserveScroll: page === undefined,
            replace: true,
            only: ['submissions', 'filters'],
            onSuccess: () =>
                setLayoutProps({
                    breadcrumbs: [
                        {
                            title:
                                form.scope === 'community'
                                    ? 'Community feed'
                                    : 'My analyses',
                            href: index(),
                        },
                    ],
                }),
        },
    );
}

watchDebounced(
    () => form.search,
    () => visit(),
    { debounce: 350 },
);

function changeScope(value: unknown): void {
    if (value === 'mine' || value === 'community') {
        form.scope = value;
        visit();
    }
}

function changeState(value: unknown): void {
    form.state = typeof value === 'string' ? value : 'all';
    visit();
}

function clearFilters(): void {
    form.state = 'all';
    form.search = '';
    visit();
}
</script>

<template>
    <Head :title="isCommunity ? 'Community feed' : 'My analyses'" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-8 sm:px-6">
        <header class="flex flex-col gap-1">
            <h1 class="font-display text-4xl tracking-tight">
                {{ isCommunity ? 'Community feed' : 'My analyses' }}
            </h1>
            <p class="text-muted-foreground">
                {{
                    isCommunity
                        ? 'Everything the community has analyzed, newest first.'
                        : 'Every text and article you have submitted for analysis.'
                }}
            </p>
        </header>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <ToggleGroup
                type="single"
                :model-value="form.scope"
                class="shrink-0 rounded-lg bg-surface p-0.5"
                @update:model-value="changeScope"
            >
                <ToggleGroupItem
                    value="mine"
                    class="px-4 data-[state=on]:bg-background data-[state=on]:shadow-xs"
                    >Mine</ToggleGroupItem
                >
                <ToggleGroupItem
                    value="community"
                    class="px-4 data-[state=on]:bg-background data-[state=on]:shadow-xs"
                    >Community</ToggleGroupItem
                >
            </ToggleGroup>

            <InputGroup
                class="flex-1 border-0 bg-surface shadow-none dark:bg-surface"
            >
                <InputGroupAddon>
                    <Search />
                </InputGroupAddon>
                <InputGroupInput
                    v-model="form.search"
                    type="search"
                    placeholder="Search titles and text…"
                    aria-label="Search analyses"
                />
            </InputGroup>

            <Select :model-value="form.state" @update:model-value="changeState">
                <SelectTrigger
                    class="w-full border-0 bg-surface shadow-none sm:w-44 dark:bg-surface"
                    aria-label="Filter by status"
                >
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem
                        v-for="state in states"
                        :key="state.value"
                        :value="state.value"
                        >{{ state.label }}</SelectItem
                    >
                </SelectContent>
            </Select>
        </div>

        <div
            v-if="submissions.data.length > 0"
            class="grid grid-cols-1 gap-0.5 rounded-2xl bg-surface p-1.5"
        >
            <SubmissionListItem
                v-for="submission in submissions.data"
                :key="submission.id"
                :submission="submission"
                :show-author="isCommunity"
            />
        </div>

        <Empty v-else class="rounded-2xl bg-surface">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <SearchX v-if="hasFilters" />
                    <Inbox v-else />
                </EmptyMedia>
                <EmptyTitle>{{
                    hasFilters ? 'No matching analyses' : 'Nothing here yet'
                }}</EmptyTitle>
                <EmptyDescription>
                    {{
                        hasFilters
                            ? 'Try a different search or status.'
                            : 'Analyses you submit will show up here.'
                    }}
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <Button
                    v-if="hasFilters"
                    variant="outline"
                    @click="clearFilters"
                    >Clear filters</Button
                >
                <Button v-else as-child>
                    <a :href="dashboard().url">Analyze a text</a>
                </Button>
            </EmptyContent>
        </Empty>

        <div
            v-if="submissions.meta.last_page > 1"
            class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between"
        >
            <p class="text-sm text-muted-foreground tabular-nums">
                Showing {{ submissions.meta.from }}–{{ submissions.meta.to }} of
                {{ submissions.meta.total }}
            </p>
            <Pagination
                v-slot="{ page }"
                :total="submissions.meta.total"
                :items-per-page="submissions.meta.per_page"
                :page="submissions.meta.current_page"
                :sibling-count="1"
                class="mx-0 w-auto"
                @update:page="visit"
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
