<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show } from '@/routes/profiles';
import type { UserSummary } from '@/types';

defineProps<{
    author: UserSummary;
}>();
</script>

<template>
    <Link
        :href="show(author.id)"
        class="relative inline-flex items-baseline gap-1 hover:underline"
        data-test="author-link"
        @click.stop
    >
        <span class="font-medium">{{ author.name }}</span>
        <span
            class="text-[10.5px] font-normal text-muted-foreground tabular-nums"
            :title="`${author.reputation} reputation`"
            >{{ author.reputation.toLocaleString() }}</span
        >
    </Link>
</template>
