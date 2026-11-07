<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowUpRight, Check, Lock } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { store as storeVote } from '@/routes/polls/votes';
import { show } from '@/routes/submissions';
import type { PublicPoll } from '@/types';

const props = defineProps<{
    poll: PublicPoll;
}>();

const editing = ref(false);
const form = useForm({
    poll_option_id: props.poll.my_option_id,
    reason: props.poll.my_reason ?? '',
});

const showResults = computed(
    () => props.poll.results !== null && !editing.value,
);

function submit(): void {
    form.submit(storeVote(props.poll.id), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
        },
    });
}
</script>

<template>
    <article class="rounded-2xl bg-surface p-5" data-test="poll-card">
        <div class="flex items-start justify-between gap-3">
            <h2 lang="hy" class="text-[0.95rem] leading-relaxed font-medium">
                {{ poll.question }}
            </h2>
            <span
                v-if="!poll.is_open"
                class="flex shrink-0 items-center gap-1 rounded-full bg-background px-2 py-0.5 text-[11px] text-muted-foreground"
                ><Lock class="size-3" /> Closed</span
            >
        </div>

        <div v-if="showResults && poll.results" class="mt-4 grid gap-2.5">
            <div
                v-for="option in poll.results.options"
                :key="option.id"
                class="grid gap-1"
            >
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span lang="hy" class="flex items-center gap-1.5">
                        <Check
                            v-if="option.id === poll.my_option_id"
                            class="size-3.5 text-brand-strong"
                        />
                        {{ option.label }}
                    </span>
                    <span class="font-mono text-xs text-muted-foreground"
                        >{{ option.share }}%</span
                    >
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-background">
                    <div
                        class="h-full rounded-full transition-[width] duration-500"
                        :class="
                            option.id === poll.my_option_id
                                ? 'bg-primary'
                                : 'bg-surface-strong'
                        "
                        :style="{ width: `${option.share}%` }"
                    />
                </div>
            </div>

            <div
                class="mt-1 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground"
            >
                <span>{{ poll.results.total_votes }} answers</span>
                <span class="flex items-center gap-3">
                    <Link
                        v-if="poll.source"
                        :href="show(poll.source.submission_id)"
                        class="flex items-center gap-1 font-medium text-brand-strong hover:underline"
                        >See the text this came from
                        <ArrowUpRight class="size-3.5"
                    /></Link>
                    <button
                        v-if="poll.is_open && poll.my_option_id"
                        type="button"
                        class="hover:text-foreground"
                        @click="editing = true"
                    >
                        Change answer
                    </button>
                </span>
            </div>
        </div>

        <form v-else class="mt-4 grid gap-2" @submit.prevent="submit">
            <div role="radiogroup" class="grid gap-1.5">
                <button
                    v-for="option in poll.options"
                    :key="option.id"
                    type="button"
                    role="radio"
                    :aria-checked="form.poll_option_id === option.id"
                    :class="
                        cn(
                            'flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-left text-sm transition-colors',
                            form.poll_option_id === option.id
                                ? 'bg-background shadow-xs ring-2 ring-primary'
                                : 'bg-background/60 hover:bg-background',
                        )
                    "
                    @click="form.poll_option_id = option.id"
                >
                    <span
                        class="flex size-4 shrink-0 items-center justify-center rounded-full border border-foreground/25"
                        :class="{
                            'border-primary bg-primary':
                                form.poll_option_id === option.id,
                        }"
                    >
                        <span
                            v-if="form.poll_option_id === option.id"
                            class="size-1.5 rounded-full bg-primary-foreground"
                        />
                    </span>
                    <span lang="hy">{{ option.label }}</span>
                </button>
            </div>

            <template v-if="form.poll_option_id">
                <Textarea
                    v-model="form.reason"
                    name="reason"
                    placeholder="Why? (optional, helps others and us understand)"
                    class="min-h-16 border-0 bg-background text-sm shadow-xs dark:bg-background"
                    maxlength="500"
                />
                <InputError
                    :message="form.errors.poll_option_id ?? form.errors.reason"
                />
                <div class="flex justify-end gap-2">
                    <Button
                        v-if="editing"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="editing = false"
                        >Cancel</Button
                    >
                    <Button type="submit" size="sm" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        {{ editing ? 'Update answer' : 'Answer' }}
                    </Button>
                </div>
            </template>
        </form>
    </article>
</template>
