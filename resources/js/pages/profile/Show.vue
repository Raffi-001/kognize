<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Award,
    Check,
    ChevronUp,
    FileSearch,
    Lock,
    MessageCircle,
    MessagesSquare,
    Vote,
} from '@lucide/vue';
import { computed } from 'vue';
import BadgeCounts from '@/components/reputation/BadgeCounts.vue';
import BadgeEmblem from '@/components/reputation/BadgeEmblem.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { badgeTiers, tierOrder } from '@/lib/reputation';
import type { BadgeTierValue, BadgeValue } from '@/lib/reputation';
import { cn } from '@/lib/utils';
import { index as leaderboard } from '@/routes/leaderboard';
import { show as showSubmission } from '@/routes/submissions';
import type { BreadcrumbItem } from '@/types';

type ProfileBadge = {
    value: BadgeValue;
    label: string;
    description: string;
    tier: BadgeTierValue;
    count: number;
};

type ProfilePrivilege = {
    value: string;
    label: string;
    description: string;
    threshold: number;
    unlocked: boolean;
};

const props = defineProps<{
    profile: {
        id: string;
        name: string;
        reputation: number;
        member_since: string;
        is_you: boolean;
    };
    stats: { notes: number; replies: number; sources: number; votes: number };
    badges: ProfileBadge[];
    privileges: ProfilePrivilege[];
    history: { date: string; amount: number }[];
    recentReputation: {
        id: number;
        reason: string;
        amount: number;
        created_at_human: string;
    }[];
    topNotes: {
        id: number;
        text: string;
        score: number;
        submission: { id: number; title: string };
        badge: { value: BadgeValue; tier: BadgeTierValue } | null;
    }[];
}>();

defineOptions({
    layout: [
        AppLayout,
        {
            breadcrumbs: [
                { title: 'Leaderboard', href: leaderboard() },
            ] satisfies BreadcrumbItem[],
        },
    ],
});

const { getInitials } = useInitials();

const tierCounts = computed(
    () =>
        Object.fromEntries(
            tierOrder.map((tier) => [
                tier,
                props.badges
                    .filter((badge) => badge.tier === tier)
                    .reduce((sum, badge) => sum + badge.count, 0),
            ]),
        ) as Record<BadgeTierValue, number>,
);

const sortedBadges = computed(() =>
    [...props.badges].sort(
        (a, b) =>
            Number(b.count > 0) - Number(a.count > 0) ||
            tierOrder.indexOf(a.tier) - tierOrder.indexOf(b.tier),
    ),
);

const next = computed(() =>
    props.privileges.find((privilege) => !privilege.unlocked),
);
const previousThreshold = computed(() => {
    const unlocked = props.privileges.filter((privilege) => privilege.unlocked);

    return unlocked.length > 0 ? unlocked[unlocked.length - 1].threshold : 0;
});
const progress = computed(() => {
    if (!next.value) {
        return 100;
    }

    const span = next.value.threshold - previousThreshold.value;

    return Math.min(
        100,
        Math.max(
            0,
            ((props.profile.reputation - previousThreshold.value) / span) * 100,
        ),
    );
});

const gained = computed(() =>
    props.history.reduce((sum, day) => sum + day.amount, 0),
);
const peak = computed(() =>
    Math.max(1, ...props.history.map((day) => Math.abs(day.amount))),
);

const stats = computed(() => [
    {
        label: 'Community notes',
        value: props.stats.notes,
        icon: MessagesSquare,
    },
    { label: 'Replies', value: props.stats.replies, icon: MessageCircle },
    { label: 'Verified sources', value: props.stats.sources, icon: FileSearch },
    { label: 'Votes cast', value: props.stats.votes, icon: Vote },
]);
</script>

<template>
    <Head :title="profile.name" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-6 sm:px-6">
        <header class="flex flex-wrap items-center gap-5">
            <Avatar class="size-16 rounded-full text-lg">
                <AvatarFallback class="bg-brand-soft text-brand-strong">{{
                    getInitials(profile.name)
                }}</AvatarFallback>
            </Avatar>
            <div class="grid min-w-0 flex-1 gap-1">
                <h1 class="truncate text-2xl font-semibold tracking-tight">
                    {{ profile.name }}
                    <span
                        v-if="profile.is_you"
                        class="text-base font-normal text-muted-foreground"
                        >(you)</span
                    >
                </h1>
                <p
                    class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground"
                >
                    <span>Member since {{ profile.member_since }}</span>
                    <BadgeCounts :counts="tierCounts" />
                </p>
            </div>
            <div class="text-right">
                <p
                    class="text-4xl font-semibold tracking-tight tabular-nums"
                    data-test="profile-reputation"
                >
                    {{ profile.reputation.toLocaleString() }}
                </p>
                <p
                    class="text-xs tracking-wider text-muted-foreground uppercase"
                >
                    Reputation
                </p>
            </div>
        </header>

        <section
            v-if="next"
            class="grid gap-2 rounded-xl border p-4"
            data-test="next-privilege"
        >
            <div class="flex items-baseline justify-between gap-4 text-sm">
                <p>
                    <span class="font-medium">Next: {{ next.label }}</span>
                    <span class="text-muted-foreground">
                        · {{ next.description }}</span
                    >
                </p>
                <p class="shrink-0 text-muted-foreground tabular-nums">
                    {{ (next.threshold - profile.reputation).toLocaleString() }}
                    to go
                </p>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-surface-strong">
                <div
                    class="h-full rounded-full bg-brand transition-[width] duration-500"
                    :style="{ width: `${progress}%` }"
                />
            </div>
        </section>

        <div class="grid gap-3 sm:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-xl bg-surface p-4"
            >
                <component
                    :is="stat.icon"
                    class="size-4 text-muted-foreground"
                />
                <p class="mt-2 text-2xl font-semibold tabular-nums">
                    {{ stat.value.toLocaleString() }}
                </p>
                <p class="text-xs text-muted-foreground">{{ stat.label }}</p>
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <section class="grid content-start gap-3">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-sm font-semibold">Last 30 days</h2>
                    <p
                        class="text-sm tabular-nums"
                        :class="
                            gained >= 0
                                ? 'text-brand-strong'
                                : 'text-destructive'
                        "
                    >
                        {{ gained >= 0 ? '+' : '' }}{{ gained }}
                    </p>
                </div>
                <div
                    class="flex h-24 items-center gap-[3px] rounded-xl border px-3"
                    role="img"
                    :aria-label="`Reputation change per day over the last 30 days, ${gained} in total`"
                >
                    <div
                        v-for="day in history"
                        :key="day.date"
                        class="relative flex h-full flex-1 flex-col justify-center"
                        :title="`${day.date}: ${day.amount >= 0 ? '+' : ''}${day.amount}`"
                    >
                        <div class="flex h-1/2 items-end">
                            <div
                                v-if="day.amount > 0"
                                class="w-full rounded-t-sm bg-brand"
                                :style="{
                                    height: `${(day.amount / peak) * 100}%`,
                                }"
                            />
                        </div>
                        <div class="h-px w-full bg-border" />
                        <div class="flex h-1/2 items-start">
                            <div
                                v-if="day.amount < 0"
                                class="w-full rounded-b-sm bg-destructive/70"
                                :style="{
                                    height: `${(-day.amount / peak) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>

                <ul
                    v-if="recentReputation.length > 0"
                    class="grid divide-y border-y text-sm"
                >
                    <li
                        v-for="event in recentReputation"
                        :key="event.id"
                        class="flex items-center gap-3 py-2"
                    >
                        <span
                            class="w-10 shrink-0 text-right font-medium tabular-nums"
                            :class="
                                event.amount >= 0
                                    ? 'text-brand-strong'
                                    : 'text-destructive'
                            "
                            >{{ event.amount >= 0 ? '+' : ''
                            }}{{ event.amount }}</span
                        >
                        <span class="flex-1">{{ event.reason }}</span>
                        <span class="text-xs text-muted-foreground">{{
                            event.created_at_human
                        }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Reputation comes from votes on community notes, sources and
                    replies.
                </p>

                <template v-if="topNotes.length > 0">
                    <h2 class="mt-4 text-sm font-semibold">
                        Top community notes
                    </h2>
                    <ul class="grid divide-y border-y">
                        <li v-for="note in topNotes" :key="note.id">
                            <Link
                                :href="showSubmission(note.submission.id)"
                                class="group flex items-start gap-3 py-3"
                            >
                                <span
                                    class="mt-0.5 inline-flex shrink-0 items-center gap-0.5 rounded-full bg-surface px-2 py-0.5 text-xs font-medium tabular-nums"
                                >
                                    <ChevronUp class="size-3" />{{ note.score }}
                                </span>
                                <span class="grid min-w-0 gap-0.5">
                                    <span
                                        lang="hy"
                                        class="line-clamp-2 text-sm group-hover:text-brand-strong"
                                        >{{ note.text }}</span
                                    >
                                    <span
                                        lang="hy"
                                        class="truncate text-xs text-muted-foreground"
                                        >on {{ note.submission.title }}</span
                                    >
                                </span>
                                <BadgeEmblem
                                    v-if="note.badge"
                                    class="ml-auto"
                                    :badge="note.badge.value"
                                    :tier="note.badge.tier"
                                    :size="24"
                                />
                            </Link>
                        </li>
                    </ul>
                </template>
            </section>

            <aside class="grid content-start gap-3">
                <h2 class="text-sm font-semibold">Privileges</h2>
                <ul class="grid gap-1.5" data-test="privileges">
                    <li
                        v-for="privilege in privileges"
                        :key="privilege.value"
                        class="flex items-start gap-2.5 rounded-lg px-2 py-1.5 text-sm"
                        :class="!privilege.unlocked && 'text-muted-foreground'"
                    >
                        <span
                            class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full"
                            :class="
                                privilege.unlocked
                                    ? 'bg-brand-soft text-brand-strong'
                                    : 'bg-surface'
                            "
                        >
                            <Check v-if="privilege.unlocked" class="size-3" />
                            <Lock v-else class="size-3" />
                        </span>
                        <span class="grid flex-1 gap-0.5">
                            <span class="flex justify-between gap-2">
                                <span class="font-medium">{{
                                    privilege.label
                                }}</span>
                                <span class="text-xs tabular-nums">{{
                                    privilege.threshold.toLocaleString()
                                }}</span>
                            </span>
                            <span class="text-xs text-muted-foreground">{{
                                privilege.description
                            }}</span>
                        </span>
                    </li>
                </ul>
            </aside>
        </div>

        <section class="grid gap-3">
            <h2 class="flex items-center gap-2 text-sm font-semibold">
                <Award class="size-4" /> Badges
            </h2>
            <ul
                class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3"
                data-test="badges"
            >
                <li
                    v-for="badge in sortedBadges"
                    :key="badge.value"
                    :class="
                        cn(
                            'flex items-center gap-3 rounded-xl p-3',
                            badge.count > 0
                                ? ['ring-1', badgeTiers[badge.tier].soft]
                                : 'border border-dashed text-muted-foreground',
                        )
                    "
                    :data-earned="badge.count > 0"
                >
                    <BadgeEmblem
                        :badge="badge.value"
                        :tier="badge.tier"
                        :earned="badge.count > 0"
                        :size="44"
                        :label="`${badge.label}${badge.count > 0 ? '' : ' (not earned yet)'}`"
                    />
                    <span class="grid content-center gap-0.5">
                        <span class="text-sm font-medium"
                            >{{ badge.label }}
                            <span
                                v-if="badge.count > 1"
                                class="font-normal tabular-nums"
                                >× {{ badge.count }}</span
                            ></span
                        >
                        <span class="text-xs opacity-80">{{
                            badge.description
                        }}</span>
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
