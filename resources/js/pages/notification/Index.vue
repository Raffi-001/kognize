<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck, Settings2 } from '@lucide/vue';
import NotificationItem from '@/components/notification/NotificationItem.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit as editPreferences } from '@/routes/notification-preferences';
import { index } from '@/routes/notifications';
import { store as markAllRead } from '@/routes/notifications/read';
import type { AppNotification, BreadcrumbItem, Paginated } from '@/types';

defineOptions({
    layout: [
        AppLayout,
        {
            breadcrumbs: [
                { title: 'Notifications', href: index() },
            ] satisfies BreadcrumbItem[],
        },
    ],
});

defineProps<{
    notifications: Paginated<AppNotification>;
}>();

function visit(page: number): void {
    router.get(
        index({ query: { page: page > 1 ? page : undefined } }).url,
        {},
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head title="Notifications" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-6 sm:px-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <PageHeader
                title="Notifications"
                description="Replies, community notes, checked sources and finished analyses."
            />
            <div class="flex items-center gap-2">
                <Button
                    v-if="$page.props.unreadNotificationsCount > 0"
                    as-child
                    variant="outline"
                    size="sm"
                >
                    <Link
                        :href="markAllRead()"
                        as="button"
                        preserve-scroll
                        data-test="notifications-page-mark-all-read"
                    >
                        <CheckCheck />
                        Mark all as read
                    </Link>
                </Button>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="editPreferences()">
                        <Settings2 />
                        Settings
                    </Link>
                </Button>
            </div>
        </div>

        <div v-if="notifications.data.length > 0" class="grid gap-1">
            <NotificationItem
                v-for="notification in notifications.data"
                :key="notification.id"
                :notification="notification"
            />
        </div>

        <Empty v-else class="rounded-xl border border-dashed">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <Bell />
                </EmptyMedia>
                <EmptyTitle>No notifications yet</EmptyTitle>
                <EmptyDescription>
                    When people reply to you or your analyses finish, you'll see
                    it here.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>

        <div
            v-if="notifications.meta.last_page > 1"
            class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between"
        >
            <p class="text-sm text-muted-foreground tabular-nums">
                Showing {{ notifications.meta.from }}–{{
                    notifications.meta.to
                }}
                of {{ notifications.meta.total }}
            </p>
            <Pagination
                v-slot="{ page }"
                :total="notifications.meta.total"
                :items-per-page="notifications.meta.per_page"
                :page="notifications.meta.current_page"
                :sibling-count="1"
                class="mx-0 w-auto"
                @update:page="visit"
            >
                <PaginationContent v-slot="{ items }">
                    <PaginationPrevious />
                    <template v-for="(item, position) in items" :key="position">
                        <PaginationItem
                            v-if="item.type === 'page'"
                            :value="item.value"
                            :is-active="item.value === page"
                        >
                            {{ item.value }}
                        </PaginationItem>
                        <PaginationEllipsis v-else :index="position" />
                    </template>
                    <PaginationNext />
                </PaginationContent>
            </Pagination>
        </div>
    </div>
</template>
