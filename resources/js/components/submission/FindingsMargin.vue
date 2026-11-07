<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import FindingItem from '@/components/submission/FindingItem.vue';
import type { Finding } from '@/lib/findings';

const COMPOSER = '__composer';
const GAP = 6;

const props = defineProps<{
    findings: Finding[];
    activeKey: string | null;
    /** Vertical offset of each finding's passage within the article. */
    anchors: Record<string, number | null>;
    composerAnchor: number | null;
    /** Align items with their passages; otherwise render a plain list. */
    floating: boolean;
    hiddenMinorCount: number;
}>();

const emit = defineEmits<{
    select: [key: string];
    showMinor: [];
    /** Where each item was placed, in the same coordinates as `anchors`. */
    layout: [tops: Record<string, number>];
}>();

const elements = new Map<string, HTMLElement>();
const tops = ref<Record<string, number>>({});
const height = ref(0);

function register(key: string, element: unknown): void {
    if (element instanceof HTMLElement) {
        if (elements.get(key) !== element) {
            elements.set(key, element);
            observer?.observe(element);
        }
    } else {
        elements.delete(key);
    }
}

/**
 * Places every item next to its passage. The composer, or else the active
 * finding, is pinned exactly to its anchor; the others are pushed up or down
 * just enough to avoid overlapping it and each other.
 */
function layout(): void {
    if (!props.floating) {
        return;
    }

    const items = [
        ...(props.composerAnchor === null
            ? []
            : [{ key: COMPOSER, anchor: props.composerAnchor }]),
        ...props.findings.map((finding) => ({
            key: finding.key,
            anchor: props.anchors[finding.key] ?? null,
        })),
    ];

    const anchored = items
        .filter(
            (item): item is { key: string; anchor: number } =>
                item.anchor !== null,
        )
        .toSorted((a, b) => a.anchor - b.anchor);
    const floatingItems = items.filter((item) => item.anchor === null);
    const ordered = [...anchored, ...floatingItems];
    const sizes = ordered.map(
        (item) => elements.get(item.key)?.offsetHeight ?? 0,
    );
    const positions: number[] = [];

    const pinnedKey =
        props.composerAnchor === null ? props.activeKey : COMPOSER;
    let pinned = ordered.findIndex(
        (item) => item.key === pinnedKey && item.anchor !== null,
    );

    if (pinned === -1) {
        pinned = 0;
        positions[0] = ordered[0]?.anchor ?? 0;
    } else {
        positions[pinned] = ordered[pinned].anchor ?? 0;
    }

    for (let index = pinned + 1; index < ordered.length; index++) {
        const below = positions[index - 1] + sizes[index - 1] + GAP;
        positions[index] = Math.max(ordered[index].anchor ?? below, below);
    }

    for (let index = pinned - 1; index >= 0; index--) {
        const above = positions[index + 1] - sizes[index] - GAP;
        positions[index] = Math.min(ordered[index].anchor ?? above, above);
    }

    const shift = Math.max(0, -(positions[0] ?? 0));
    const next: Record<string, number> = {};

    ordered.forEach((item, index) => {
        next[item.key] = positions[index] + shift;
    });

    tops.value = next;
    emit('layout', next);
    height.value =
        ordered.length > 0
            ? positions[ordered.length - 1] + shift + sizes[ordered.length - 1]
            : 0;
}

let observer: ResizeObserver | null = null;
let frame = 0;

function scheduleLayout(): void {
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(layout);
}

onMounted(() => {
    observer = new ResizeObserver(scheduleLayout);
    elements.forEach((element) => observer?.observe(element));
    scheduleLayout();
});

onBeforeUnmount(() => {
    observer?.disconnect();
    cancelAnimationFrame(frame);
});

watch(
    () => [
        props.findings,
        props.activeKey,
        props.anchors,
        props.composerAnchor,
        props.floating,
    ],
    async () => {
        await nextTick();
        scheduleLayout();
    },
    { deep: true },
);

function positionStyle(key: string): Record<string, string> | undefined {
    if (!props.floating) {
        return undefined;
    }

    return {
        position: 'absolute',
        left: '0',
        right: '0',
        top: `${tops.value[key] ?? 0}px`,
        transition: 'top 220ms cubic-bezier(0.2, 0, 0, 1)',
    };
}
</script>

<template>
    <div
        :class="floating ? 'relative' : 'grid gap-1'"
        :style="
            floating
                ? { minHeight: `${height}px`, marginBottom: '60svh' }
                : undefined
        "
    >
        <div
            v-if="$slots.composer"
            :ref="(element) => register(COMPOSER, element)"
            :style="positionStyle(COMPOSER)"
            class="z-10"
        >
            <slot name="composer" />
        </div>

        <div
            v-for="finding in findings"
            :key="finding.key"
            :ref="(element) => register(finding.key, element)"
            :style="positionStyle(finding.key)"
            :class="{ 'z-[5]': finding.key === activeKey }"
        >
            <FindingItem
                :finding="finding"
                :active="finding.key === activeKey"
                :dimmed="activeKey !== null && finding.key !== activeKey"
                @select="emit('select', finding.key)"
            />
        </div>

        <button
            v-if="hiddenMinorCount > 0"
            type="button"
            class="w-full rounded-lg px-3 py-2 text-left text-xs text-muted-foreground transition-colors hover:bg-surface hover:text-foreground"
            :style="
                floating
                    ? { position: 'absolute', top: `${height + 8}px` }
                    : undefined
            "
            @click="emit('showMinor')"
        >
            <span
                class="mr-2 text-[10px] font-semibold tracking-wider uppercase"
                >Minor</span
            >
            {{ hiddenMinorCount }} low-severity
            {{ hiddenMinorCount === 1 ? 'finding' : 'findings' }} hidden · Show
        </button>
    </div>
</template>
