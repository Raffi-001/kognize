<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import CommandPalette from '@/components/CommandPalette.vue';
import { Button } from '@/components/ui/button';
import { Kbd } from '@/components/ui/kbd';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const paletteOpen = ref(false);
const usage = computed(() => page.props.auth.usage);
const isDashboard = computed(() => page.url.split('?')[0] === dashboard().url);

const remainingRatio = computed(() => {
    const meter = usage.value?.submissions;

    return meter && meter.limit > 0 ? meter.remaining / meter.limit : 0;
});
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-2 bg-background/85 px-4 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:rounded-t-lg md:px-6"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                class="hidden h-8 w-64 items-center gap-2 rounded-lg bg-surface px-2.5 text-xs text-muted-foreground transition-colors hover:bg-surface-strong md:flex"
                data-test="command-palette-trigger"
                @click="paletteOpen = true"
            >
                <Search class="size-3.5" />
                Search or jump to…
                <Kbd class="ml-auto">⌘K</Kbd>
            </button>

            <Tooltip v-if="usage">
                <TooltipTrigger as-child>
                    <div
                        class="hidden items-center gap-2.5 text-xs text-muted-foreground sm:flex"
                        data-test="usage-meter"
                    >
                        <span>Analyses</span>
                        <div
                            class="h-1.5 w-24 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-primary transition-[width] duration-500"
                                :style="{ width: `${remainingRatio * 100}%` }"
                            />
                        </div>
                        <span class="font-medium text-foreground tabular-nums"
                            >{{ usage.submissions.remaining }} left</span
                        >
                    </div>
                </TooltipTrigger>
                <TooltipContent>
                    {{ usage.submissions.used }} of
                    {{ usage.submissions.limit }} daily analyses used
                </TooltipContent>
            </Tooltip>

            <Button v-if="!isDashboard" as-child size="sm">
                <Link :href="dashboard()">
                    <Plus />
                    <span class="hidden sm:inline">New analysis</span>
                </Link>
            </Button>
        </div>
    </header>

    <CommandPalette v-model:open="paletteOpen" />
</template>
