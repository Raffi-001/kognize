<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { cn } from '@/lib/utils';
import { store } from '@/routes/votes';
import type { VotableType, VoteValue } from '@/types';

const props = defineProps<{
    type: VotableType;
    id: number;
    score: number;
    myVote: VoteValue | null;
    /** You cannot vote on what you wrote. */
    own?: boolean;
    size?: 'sm' | 'xs';
}>();

const pending = ref(false);

function vote(value: VoteValue): void {
    if (props.own || pending.value) {
        return;
    }

    router.post(
        store().url,
        { votable_type: props.type, votable_id: props.id, value },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (pending.value = true),
            onFinish: () => (pending.value = false),
            onError: (errors) => {
                toast.error(
                    errors.vote ??
                        Object.values(errors)[0] ??
                        'Could not vote.',
                );
            },
        },
    );
}
</script>

<template>
    <span
        :class="
            cn(
                'relative inline-flex items-center rounded-full bg-surface tabular-nums',
                size === 'xs' ? 'h-5 text-[11px]' : 'h-6 text-xs',
                pending && 'opacity-60',
            )
        "
        :data-test="`vote-${type}-${id}`"
    >
        <button
            type="button"
            :disabled="own"
            :aria-pressed="myVote === 1"
            :aria-label="
                own ? 'You cannot vote on your own contribution' : 'Upvote'
            "
            :class="
                cn(
                    'flex h-full items-center rounded-l-full px-1 transition-colors disabled:cursor-default disabled:opacity-40',
                    myVote === 1
                        ? 'text-brand-strong'
                        : 'text-muted-foreground hover:text-foreground',
                )
            "
            data-test="vote-up"
            @click.stop="vote(1)"
        >
            <ChevronUp :class="size === 'xs' ? 'size-3' : 'size-3.5'" />
        </button>
        <span
            :class="
                cn(
                    'min-w-3 text-center font-medium',
                    myVote === 1 && 'text-brand-strong',
                    myVote === -1 && 'text-destructive',
                )
            "
            data-test="vote-score"
            >{{ score }}</span
        >
        <button
            type="button"
            :disabled="own"
            :aria-pressed="myVote === -1"
            :aria-label="
                own ? 'You cannot vote on your own contribution' : 'Downvote'
            "
            :class="
                cn(
                    'flex h-full items-center rounded-r-full px-1 transition-colors disabled:cursor-default disabled:opacity-40',
                    myVote === -1
                        ? 'text-destructive'
                        : 'text-muted-foreground hover:text-foreground',
                )
            "
            data-test="vote-down"
            @click.stop="vote(-1)"
        >
            <ChevronDown :class="size === 'xs' ? 'size-3' : 'size-3.5'" />
        </button>
    </span>
</template>
