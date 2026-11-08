<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { index as invitations } from '@/routes/invitations';

const page = usePage();
const usage = computed(() => page.props.auth.usage);

const percentage = computed(() => {
    const meter = usage.value?.submissions;

    return meter && meter.limit > 0 ? (meter.used / meter.limit) * 100 : 0;
});
</script>

<template>
    <div
        v-if="usage"
        class="rounded-lg bg-background p-3.5 shadow-xs group-data-[collapsible=icon]:hidden"
    >
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium">Daily analyses</p>
            <span
                class="rounded-full bg-brand-soft px-2 py-0.5 text-[11px] font-medium text-brand-strong"
                >{{ usage.plan_label }}</span
            >
        </div>
        <p class="mt-1 text-xs text-muted-foreground">
            {{ usage.submissions.remaining }} of
            {{ usage.submissions.limit }} left today. Resets at midnight.
        </p>
        <Progress :model-value="percentage" class="mt-3 h-1.5" />
        <Button
            v-if="usage.invitations.remaining > 0"
            as-child
            size="sm"
            class="mt-3 w-full"
        >
            <Link :href="invitations()">
                <Sparkles />
                Invite a colleague
            </Link>
        </Button>
    </div>
</template>
