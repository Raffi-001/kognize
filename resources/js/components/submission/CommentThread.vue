<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Flag, SendHorizontal } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import AuthorLink from '@/components/reputation/AuthorLink.vue';
import VoteControl from '@/components/reputation/VoteControl.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useInitials } from '@/composables/useInitials';
import { useReportDialog } from '@/composables/useReportDialog';
import type { Comment } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

defineProps<{
    comments: Comment[];
    action: RouteFormDefinition<'post'>;
}>();

const { getInitials } = useInitials();
const { report } = useReportDialog();
</script>

<template>
    <div class="grid gap-3">
        <p v-if="comments.length === 0" class="text-xs text-muted-foreground">
            No replies yet. Start the discussion.
        </p>

        <ul v-else class="grid gap-3">
            <li
                v-for="comment in comments"
                :key="comment.id"
                class="group/comment flex gap-2.5"
            >
                <Avatar class="size-6 rounded-full">
                    <AvatarFallback class="text-[10px]">{{
                        getInitials(comment.author.name)
                    }}</AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5 text-xs">
                        <AuthorLink :author="comment.author" />
                        <span
                            v-if="comment.is_owner"
                            class="text-muted-foreground"
                            >(you)</span
                        >
                        <span class="text-muted-foreground">·</span>
                        <span class="text-muted-foreground">{{
                            comment.created_at_human
                        }}</span>
                        <VoteControl
                            class="ml-auto"
                            size="xs"
                            type="comment"
                            :id="comment.id"
                            :score="comment.score"
                            :my-vote="comment.my_vote"
                            :own="comment.is_owner"
                        />
                        <button
                            v-if="!comment.is_owner"
                            type="button"
                            class="text-muted-foreground opacity-0 transition-opacity group-hover/comment:opacity-100 hover:text-destructive focus-visible:opacity-100"
                            aria-label="Report reply"
                            @click="report('comment', comment.id)"
                        >
                            <Flag class="size-3.5" />
                        </button>
                    </div>
                    <p class="mt-0.5 text-sm whitespace-pre-wrap">
                        {{ comment.text }}
                    </p>
                </div>
            </li>
        </ul>

        <Form
            v-bind="action"
            :options="{ preserveScroll: true, preserveState: true }"
            reset-on-success
            v-slot="{ errors, processing }"
            class="grid gap-2"
        >
            <Textarea
                name="text"
                placeholder="Write a reply…"
                class="min-h-16 resize-none border-0 bg-background text-sm shadow-xs dark:bg-background"
                required
                maxlength="1000"
            />
            <InputError :message="errors.text" />
            <div class="flex justify-end">
                <Button type="submit" size="sm" :disabled="processing">
                    <Spinner v-if="processing" />
                    <SendHorizontal v-else />
                    Reply
                </Button>
            </div>
        </Form>
    </div>
</template>
