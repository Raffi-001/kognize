<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { MessagesSquare } from '@lucide/vue';
import { computed } from 'vue';
import PollCard from '@/components/poll/PollCard.vue';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/polls';
import type { BreadcrumbItem, PublicPoll } from '@/types';

defineOptions({
    layout: [
        AppLayout,
        {
            breadcrumbs: [
                { title: 'Public questions', href: index() },
            ] satisfies BreadcrumbItem[],
        },
    ],
});

const props = defineProps<{
    polls: PublicPoll[];
}>();

const unanswered = computed(
    () =>
        props.polls.filter((poll) => poll.is_open && !poll.my_option_id).length,
);
</script>

<template>
    <Head title="Public questions" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6">
        <header class="flex flex-col gap-1">
            <h1 class="font-display text-4xl">Public questions</h1>
            <p class="text-muted-foreground">
                Questions of values raised by analyzed texts, asked without
                their context so you can judge the reasoning, not the side.
                Results and the source appear after you answer.
            </p>
            <p
                v-if="unanswered > 0"
                class="text-sm font-medium text-brand-strong"
            >
                {{ unanswered }} waiting for your answer
            </p>
        </header>

        <div v-if="polls.length > 0" class="grid gap-4">
            <PollCard v-for="poll in polls" :key="poll.id" :poll="poll" />
        </div>

        <Empty v-else class="rounded-2xl bg-surface">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <MessagesSquare />
                </EmptyMedia>
                <EmptyTitle>No public questions yet</EmptyTitle>
                <EmptyDescription>
                    When an analysis raises a question of values, it appears
                    here once a moderator has reviewed it.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    </div>
</template>
