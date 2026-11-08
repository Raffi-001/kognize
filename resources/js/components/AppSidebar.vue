<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CircleHelp,
    Layers,
    MessagesSquare,
    ShieldCheck,
    ScanSearch,
    Settings,
    Trophy,
    UserPlus,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import UsageCard from '@/components/UsageCard.vue';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as invitations } from '@/routes/invitations';
import { index as leaderboard } from '@/routes/leaderboard';
import { index as polls } from '@/routes/polls';
import { index as questions } from '@/routes/questions';
import { index as submissions } from '@/routes/submissions';
import { edit as profile } from '@/routes/user-profile';
import type { NavItem } from '@/types';

type NavGroup = {
    label?: string;
    items: (NavItem & {
        keys?: string;
        badge?: number;
        /** A count of things for everyone, not something waiting for you. */
        quietBadge?: boolean;
        external?: boolean;
    })[];
};

const page = usePage();
const currentUrl = computed(() => new URL(page.url, 'http://localhost'));

function matches(path: string, query?: Record<string, string>): boolean {
    if (currentUrl.value.pathname !== path) {
        return false;
    }

    return Object.entries(query ?? {}).every(
        ([key, value]) => currentUrl.value.searchParams.get(key) === value,
    );
}

const groups = computed<NavGroup[]>(() => [
    {
        items: [
            {
                title: 'Analyze text',
                href: dashboard(),
                icon: ScanSearch,
                keys: 'N',
                isActive: matches(dashboard().url),
            },
        ],
    },
    {
        label: 'Workspace',
        items: [
            {
                title: 'Analyses',
                href: submissions(),
                icon: Layers,
                keys: 'G A',
                isActive: currentUrl.value.pathname.startsWith(
                    submissions().url,
                ),
            },
            {
                title: 'Unresolved issues',
                href: questions(),
                icon: CircleHelp,
                keys: 'G U',
                badge: page.props.openQuestionsCount,
                quietBadge: true,
                isActive: currentUrl.value.pathname.startsWith(questions().url),
            },
        ],
    },
    {
        label: 'Community',
        items: [
            {
                title: 'Public questions',
                href: polls(),
                icon: MessagesSquare,
                keys: 'G Q',
                badge: page.props.openPollsCount,
                isActive: matches(polls().url),
            },
            {
                title: 'Leaderboard',
                href: leaderboard(),
                icon: Trophy,
                keys: 'G L',
                isActive: matches(leaderboard().url),
            },
        ],
    },
    {
        label: 'More',
        items: [
            {
                title: 'Invitations',
                href: invitations(),
                icon: UserPlus,
                keys: 'G I',
                isActive: matches(invitations().url),
            },
            {
                title: 'Settings',
                href: profile(),
                icon: Settings,
                isActive: currentUrl.value.pathname.startsWith('/settings'),
            },
            ...(page.props.adminUrl
                ? [
                      {
                          title: 'Moderation panel',
                          href: page.props.adminUrl,
                          icon: ShieldCheck,
                          external: true,
                      },
                  ]
                : []),
        ],
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup
                v-for="(group, index) in groups"
                :key="group.label ?? index"
                class="px-2 py-1"
            >
                <SidebarGroupLabel
                    v-if="group.label"
                    class="text-[11px] font-semibold tracking-wider uppercase"
                    >{{ group.label }}</SidebarGroupLabel
                >
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="item in group.items"
                        :key="item.title"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="item.isActive"
                            :tooltip="item.title"
                            class="h-9 data-[active=true]:font-medium data-[active=true]:[&>svg]:text-brand-strong"
                        >
                            <a v-if="item.external" :href="toUrl(item.href)">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </a>
                            <Link v-else :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <span
                                    v-if="item.badge"
                                    class="ml-auto rounded-full px-1.5 text-[10.5px] leading-4 font-semibold tabular-nums group-data-[collapsible=icon]:hidden"
                                    :class="
                                        item.quietBadge
                                            ? 'bg-sidebar-accent text-muted-foreground'
                                            : 'bg-primary text-primary-foreground'
                                    "
                                    >{{ item.badge }}</span
                                >
                                <span
                                    v-else-if="item.keys"
                                    class="ml-auto font-mono text-[10.5px] text-muted-foreground/70 group-data-[collapsible=icon]:hidden"
                                    >{{ item.keys }}</span
                                >
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="gap-3">
            <UsageCard />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
