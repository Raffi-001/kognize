<script setup lang="ts">
import { Link, router, usePage, usePoll } from '@inertiajs/vue3';
import { Bell, CheckCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import NotificationItem from '@/components/notification/NotificationItem.vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/notifications';
import { store as markAllRead } from '@/routes/notifications/read';

const page = usePage();
const open = ref(false);
const loading = ref(false);

const unread = computed(() => page.props.unreadNotificationsCount);
const notifications = computed(() => page.props.latestNotifications ?? []);
const badge = computed(() => (unread.value > 9 ? '9+' : String(unread.value)));

usePoll(30_000, { only: ['unreadNotificationsCount'] });

function load(value: boolean): void {
    open.value = value;

    if (value) {
        router.reload({
            only: ['latestNotifications', 'unreadNotificationsCount'],
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        });
    }
}
</script>

<template>
    <Popover :open="open" @update:open="load">
        <PopoverTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative"
                :aria-label="
                    unread > 0
                        ? `Notifications, ${unread} unread`
                        : 'Notifications'
                "
                data-test="notification-bell"
            >
                <Bell />
                <span
                    v-if="unread > 0"
                    class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-none font-semibold text-white tabular-nums"
                    data-test="notification-count"
                    >{{ badge }}</span
                >
            </Button>
        </PopoverTrigger>
        <PopoverContent align="end" class="w-96 max-w-[calc(100vw-2rem)] p-0">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <p class="text-sm font-semibold">Notifications</p>
                <Link
                    v-if="unread > 0"
                    :href="markAllRead()"
                    as="button"
                    preserve-scroll
                    :only="['latestNotifications', 'unreadNotificationsCount']"
                    class="inline-flex items-center gap-1 text-xs font-medium text-brand-strong hover:underline"
                    data-test="notifications-mark-all-read"
                >
                    <CheckCheck class="size-3.5" />
                    Mark all as read
                </Link>
            </div>

            <div class="max-h-[26rem] overflow-y-auto p-1.5">
                <div
                    v-if="loading && notifications.length === 0"
                    class="flex justify-center py-8"
                >
                    <Spinner />
                </div>
                <p
                    v-else-if="notifications.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    You're all caught up.
                </p>
                <NotificationItem
                    v-for="notification in notifications"
                    v-else
                    :key="notification.id"
                    :notification="notification"
                    compact
                    @click="open = false"
                />
            </div>

            <div class="border-t p-1.5">
                <Button
                    as-child
                    variant="ghost"
                    size="sm"
                    class="w-full"
                    @click="open = false"
                >
                    <Link :href="index()">View all notifications</Link>
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
