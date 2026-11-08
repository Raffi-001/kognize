<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    CircleHelp,
    FileText,
    Layers,
    MessagesSquare,
    Moon,
    Newspaper,
    ScanSearch,
    Settings,
    Sun,
    Trophy,
    UserPlus,
} from '@lucide/vue';
import { useEventListener } from '@vueuse/core';
import { computed, ref } from 'vue';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
    CommandShortcut,
} from '@/components/ui/command';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard } from '@/routes';
import { index as invitations } from '@/routes/invitations';
import { index as leaderboard } from '@/routes/leaderboard';
import { index as polls } from '@/routes/polls';
import { index as questions } from '@/routes/questions';
import { index as submissions, show } from '@/routes/submissions';
import { edit as profile } from '@/routes/user-profile';

const open = defineModel<boolean>('open', { default: false });

const page = usePage();
const recent = computed(() => page.props.recentSubmissions ?? []);
const { resolvedAppearance, updateAppearance } = useAppearance();

const destinations = [
    {
        label: 'Analyze text',
        icon: ScanSearch,
        url: dashboard().url,
        keys: 'N',
    },
    {
        label: 'Analyses',
        icon: Layers,
        url: submissions().url,
        keys: 'G A',
    },
    {
        label: 'Community feed',
        icon: Newspaper,
        url: submissions({ query: { scope: 'community' } }).url,
        keys: 'G F',
    },
    {
        label: 'Unresolved issues',
        icon: CircleHelp,
        url: questions().url,
        keys: 'G U',
    },
    {
        label: 'Public questions',
        icon: MessagesSquare,
        url: polls().url,
        keys: 'G Q',
    },
    {
        label: 'Leaderboard',
        icon: Trophy,
        url: leaderboard().url,
        keys: 'G L',
    },
    {
        label: 'Invitations',
        icon: UserPlus,
        url: invitations().url,
        keys: 'G I',
    },
    { label: 'Settings', icon: Settings, url: profile().url, keys: '' },
];

function go(url: string): void {
    open.value = false;
    router.visit(url);
}

function toggleTheme(): void {
    open.value = false;
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}

/** "g" followed by a letter jumps to a section, GitHub-style. */
const pendingG = ref(false);
let pendingTimer: ReturnType<typeof setTimeout> | undefined;

useEventListener(document, 'keydown', (event: KeyboardEvent) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        open.value = !open.value;

        return;
    }

    const target = event.target instanceof Element ? event.target : null;

    if (
        open.value ||
        event.metaKey ||
        event.ctrlKey ||
        event.altKey ||
        target?.closest('input, textarea, select, [contenteditable="true"]')
    ) {
        return;
    }

    if (pendingG.value) {
        pendingG.value = false;
        clearTimeout(pendingTimer);

        const sequence: Record<string, string> = {
            a: submissions().url,
            h: submissions().url,
            u: questions().url,
            f: submissions({ query: { scope: 'community' } }).url,
            i: invitations().url,
            q: polls().url,
            l: leaderboard().url,
        };

        if (sequence[event.key]) {
            event.preventDefault();
            router.visit(sequence[event.key]);
        }

        return;
    }

    if (event.key === 'g') {
        pendingG.value = true;
        pendingTimer = setTimeout(() => (pendingG.value = false), 900);
    } else if (event.key === 'n') {
        event.preventDefault();
        router.visit(dashboard().url);
    }
});
</script>

<template>
    <CommandDialog
        v-model:open="open"
        title="Command palette"
        description="Jump to a page or a recent analysis"
    >
        <CommandInput placeholder="Search pages and recent analyses…" />
        <CommandList>
            <CommandEmpty>No results found.</CommandEmpty>
            <CommandGroup heading="Go to">
                <CommandItem
                    v-for="destination in destinations"
                    :key="destination.label"
                    :value="destination.label"
                    @select="go(destination.url)"
                >
                    <component :is="destination.icon" />
                    {{ destination.label }}
                    <CommandShortcut v-if="destination.keys">{{
                        destination.keys
                    }}</CommandShortcut>
                </CommandItem>
            </CommandGroup>
            <template v-if="recent.length > 0">
                <CommandSeparator />
                <CommandGroup heading="Recent analyses">
                    <CommandItem
                        v-for="item in recent"
                        :key="item.id"
                        :value="`${item.title} #${item.id}`"
                        @select="go(show(item.id).url)"
                    >
                        <FileText />
                        <span class="truncate" lang="hy">{{ item.title }}</span>
                    </CommandItem>
                </CommandGroup>
            </template>
            <CommandSeparator />
            <CommandGroup heading="Preferences">
                <CommandItem value="Toggle theme" @select="toggleTheme">
                    <Moon v-if="resolvedAppearance === 'light'" />
                    <Sun v-else />
                    Switch to
                    {{ resolvedAppearance === 'dark' ? 'light' : 'dark' }} mode
                </CommandItem>
            </CommandGroup>
        </CommandList>
    </CommandDialog>
</template>
