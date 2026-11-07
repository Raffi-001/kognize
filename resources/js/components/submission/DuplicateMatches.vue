<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, CopyCheck } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/submissions';
import type { DuplicateMatch } from '@/types';

defineProps<{
    duplicates: DuplicateMatch[];
    processing?: boolean;
}>();

defineEmits<{
    proceed: [];
    dismiss: [];
}>();
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-questions-soft/70">
        <div class="flex items-start gap-3 px-4 pt-4">
            <div
                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-background text-questions shadow-xs"
            >
                <CopyCheck class="size-4" />
            </div>
            <div>
                <p class="text-sm font-medium">
                    This may already have been analyzed
                </p>
                <p class="text-sm text-muted-foreground">
                    Open an existing analysis to save one of your daily
                    analyses, or continue anyway.
                </p>
            </div>
        </div>

        <ul class="mt-3 grid grid-cols-1 gap-1 px-1.5">
            <li v-for="duplicate in duplicates" :key="duplicate.id">
                <Link
                    :href="show(duplicate.id)"
                    class="group flex items-start gap-3 rounded-xl bg-background/70 px-3 py-2.5 transition-colors hover:bg-background"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full bg-questions-soft px-2 py-0.5 text-[11px] font-medium text-questions"
                                >{{ duplicate.match_label }}</span
                            >
                            <span
                                v-if="duplicate.similarity < 1"
                                class="text-xs text-muted-foreground tabular-nums"
                                >{{ Math.round(duplicate.similarity * 100) }}%
                                similar</span
                            >
                            <span class="text-xs text-muted-foreground">{{
                                duplicate.created_at_human
                            }}</span>
                        </div>
                        <p class="mt-1 line-clamp-2 text-sm text-foreground/80">
                            {{ duplicate.preview }}
                        </p>
                    </div>
                    <ArrowUpRight
                        class="mt-1 size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-foreground"
                    />
                </Link>
            </li>
        </ul>

        <div class="flex justify-end gap-2 px-4 py-3">
            <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="$emit('dismiss')"
                >Edit text</Button
            >
            <Button
                type="button"
                size="sm"
                variant="outline"
                :disabled="processing"
                @click="$emit('proceed')"
                >Analyze anyway</Button
            >
        </div>
    </div>
</template>
