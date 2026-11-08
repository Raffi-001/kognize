<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { MessagesSquare } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
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

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6">
        <PageHeader
            title="Public questions"
            description="Questions of values, asked without their context so you judge the reasoning, not the side. Results and the source appear after you answer."
        >
            <template v-if="unanswered > 0" #actions>
                <span
                    class="rounded-full bg-brand-soft px-2.5 py-1 text-xs font-medium text-brand-strong"
                    >{{ unanswered }} waiting for your answer</span
                >
            </template>
        </PageHeader>

        <div v-if="polls.length > 0" class="grid max-w-3xl gap-4">
            <PollCard v-for="poll in polls" :key="poll.id" :poll="poll" />
        </div>

        <Empty v-else class="rounded-xl bg-surface">
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
