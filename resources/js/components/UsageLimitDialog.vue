<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Hourglass } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { index as submissions } from '@/routes/submissions';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    {
        title: 'Daily limit reached',
        description:
            "You've used all of today's analyses. Your allowance resets at midnight. In the meantime, you can explore what the community has analyzed.",
    },
);

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="items-center text-center sm:text-center">
                <div
                    class="mb-2 flex size-12 items-center justify-center rounded-full bg-questions-soft text-questions"
                >
                    <Hourglass class="size-6" />
                </div>
                <DialogTitle class="font-display text-2xl">{{
                    title
                }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:justify-center">
                <DialogClose as-child>
                    <Button variant="outline">Close</Button>
                </DialogClose>
                <Button as-child>
                    <Link :href="submissions({ query: { scope: 'community' } })"
                        >Browse the community feed</Link
                    >
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
