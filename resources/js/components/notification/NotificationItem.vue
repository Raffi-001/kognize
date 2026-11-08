<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    fallbackNotificationKind,
    notificationKinds,
} from '@/lib/notifications';
import { cn } from '@/lib/utils';
import { update } from '@/routes/notifications';
import type { AppNotification } from '@/types';

const props = defineProps<{
    notification: AppNotification;
    compact?: boolean;
}>();

const meta = computed(() =>
    props.notification.kind
        ? notificationKinds[props.notification.kind]
        : fallbackNotificationKind,
);
</script>

<template>
    <Link
        :href="update(notification.id)"
        as="button"
        :class="
            cn(
                'group flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition-colors hover:bg-surface focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none',
                !notification.is_read && 'bg-brand-soft/40',
            )
        "
        :data-read="notification.is_read"
        data-test="notification"
    >
        <span
            :class="
                cn(
                    'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full',
                    meta.class,
                )
            "
        >
            <component :is="meta.icon" class="size-4" />
        </span>
        <span class="grid min-w-0 flex-1 gap-0.5">
            <span
                :class="
                    cn(
                        'text-sm leading-snug',
                        notification.is_read
                            ? 'text-foreground/80'
                            : 'font-medium',
                    )
                "
                >{{ notification.headline }}</span
            >
            <span
                v-if="notification.excerpt"
                lang="hy"
                :class="
                    cn(
                        'text-xs text-muted-foreground',
                        compact && 'line-clamp-1',
                    )
                "
                >{{ notification.excerpt }}</span
            >
            <span class="text-xs text-muted-foreground">{{
                notification.created_at_human
            }}</span>
        </span>
        <span
            v-if="!notification.is_read"
            class="mt-2 size-2 shrink-0 rounded-full bg-brand"
            aria-label="Unread"
        />
    </Link>
</template>
