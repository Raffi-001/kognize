<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Globe, Type } from '@lucide/vue';
import SubmissionStateBadge from '@/components/submission/SubmissionStateBadge.vue';
import { analysisTypes, aspects } from '@/lib/analysis';
import { show } from '@/routes/submissions';
import type { Submission } from '@/types';

withDefaults(
    defineProps<{
        submission: Submission;
        showAuthor?: boolean;
    }>(),
    {
        showAuthor: false,
    },
);
</script>

<template>
    <Link
        :href="show(submission.id)"
        class="group flex min-w-0 items-center gap-4 py-3"
        data-test="submission-list-item"
    >
        <div
            class="hidden size-9 shrink-0 items-center justify-center rounded-lg bg-surface text-muted-foreground sm:flex"
        >
            <Globe v-if="submission.source" class="size-4" />
            <Type v-else class="size-4" />
        </div>

        <div class="min-w-0 flex-1">
            <p
                class="truncate text-sm font-medium group-hover:text-brand-strong"
                lang="hy"
            >
                {{ submission.title }}
            </p>
            <p
                class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground"
            >
                <span v-if="showAuthor">{{ submission.author.name }}</span>
                <span v-if="showAuthor" aria-hidden="true">·</span>
                <span v-if="submission.source_host">{{
                    submission.source_host
                }}</span>
                <span v-if="submission.source_host" aria-hidden="true">·</span>
                <span class="font-mono text-[11px]"
                    >{{ submission.word_count }} words</span
                >
                <span aria-hidden="true">·</span>
                <time :datetime="submission.created_at">{{
                    submission.created_at_human
                }}</time>
            </p>
        </div>

        <div
            v-if="submission.state === 'analyzed'"
            class="hidden items-center gap-3 md:flex"
        >
            <span
                v-for="type in analysisTypes"
                :key="type"
                class="flex items-center gap-1 text-xs tabular-nums"
                :class="aspects[type].textClass"
                :title="`${submission.counts[type]} ${aspects[type].label.toLowerCase()}`"
            >
                <component :is="aspects[type].icon" class="size-3.5" />
                {{ submission.counts[type] }}
            </span>
        </div>

        <SubmissionStateBadge
            v-else
            :state="submission.state"
            :label="submission.state_label"
        />

        <ChevronRight
            class="size-4 shrink-0 text-muted-foreground/60 transition-transform group-hover:translate-x-0.5 group-hover:text-foreground"
        />
    </Link>
</template>
