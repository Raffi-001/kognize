<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Trophy } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import BadgeCounts from '@/components/reputation/BadgeCounts.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BadgeTierValue } from '@/lib/reputation';
import { cn } from '@/lib/utils';
import { index } from '@/routes/leaderboard';
import { show } from '@/routes/profiles';
import type { BreadcrumbItem } from '@/types';

type Period = 'week' | 'month' | 'all';

defineOptions({
    layout: [
        AppLayout,
        {
            breadcrumbs: [
                { title: 'Leaderboard', href: index() },
            ] satisfies BreadcrumbItem[],
        },
    ],
});

const props = defineProps<{
    period: Period;
    leaders: {
        rank: number;
        id: string;
        name: string;
        reputation: number;
        gained: number;
        badges: Record<BadgeTierValue, number>;
    }[];
}>();

const page = usePage();
const { getInitials } = useInitials();

const medal: Record<number, string> = {
    1: 'bg-amber-400 text-amber-950',
    2: 'bg-slate-300 text-slate-900',
    3: 'bg-orange-700/70 text-white',
};

function changePeriod(value: unknown): void {
    if (value === 'week' || value === 'month' || value === 'all') {
        router.get(
            index({ query: { period: value === 'week' ? undefined : value } })
                .url,
            {},
            { preserveState: true, replace: true },
        );
    }
}
</script>

<template>
    <Head title="Leaderboard" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-6 sm:px-6">
        <PageHeader
            title="Leaderboard"
            description="Readers whose community notes, sources and replies others found most useful."
        />

        <ToggleGroup
            type="single"
            :model-value="props.period"
            class="self-start rounded-lg bg-surface p-0.5"
            @update:model-value="changePeriod"
        >
            <ToggleGroupItem value="week" class="px-3"
                >This week</ToggleGroupItem
            >
            <ToggleGroupItem value="month" class="px-3"
                >This month</ToggleGroupItem
            >
            <ToggleGroupItem value="all" class="px-3">All time</ToggleGroupItem>
        </ToggleGroup>

        <ol v-if="leaders.length > 0" class="grid divide-y border-y">
            <li v-for="leader in leaders" :key="leader.id">
                <Link
                    :href="show(leader.id)"
                    :class="
                        cn(
                            'group flex items-center gap-4 py-3',
                            leader.id === page.props.auth.user?.id &&
                                '-mx-3 rounded-lg bg-brand-soft/40 px-3',
                        )
                    "
                    data-test="leader"
                >
                    <span
                        :class="
                            cn(
                                'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                                medal[leader.rank] ?? 'text-muted-foreground',
                            )
                        "
                        >{{ leader.rank }}</span
                    >
                    <Avatar class="size-8 rounded-full">
                        <AvatarFallback class="text-xs">{{
                            getInitials(leader.name)
                        }}</AvatarFallback>
                    </Avatar>
                    <span class="grid min-w-0 flex-1 gap-0.5">
                        <span
                            class="truncate text-sm font-medium group-hover:text-brand-strong"
                            >{{ leader.name }}</span
                        >
                        <BadgeCounts
                            class="text-muted-foreground"
                            :counts="leader.badges"
                        />
                    </span>
                    <span class="text-right">
                        <span
                            class="block text-sm font-semibold tabular-nums"
                            >{{
                                period === 'all'
                                    ? leader.reputation.toLocaleString()
                                    : `+${leader.gained.toLocaleString()}`
                            }}</span
                        >
                        <span
                            v-if="period !== 'all'"
                            class="block text-xs text-muted-foreground tabular-nums"
                            >{{
                                leader.reputation.toLocaleString()
                            }}
                            total</span
                        >
                    </span>
                </Link>
            </li>
        </ol>

        <Empty v-else class="rounded-xl border border-dashed">
            <EmptyHeader>
                <EmptyMedia variant="icon"><Trophy /></EmptyMedia>
                <EmptyTitle>No reputation earned yet</EmptyTitle>
                <EmptyDescription>
                    Upvote community notes, sources and replies that help you
                    read more critically. Their authors will show up here.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    </div>
</template>
