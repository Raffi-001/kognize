<script setup lang="ts">
import { Head, setLayoutProps, usePoll } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    CircleAlert,
    CircleCheck,
    ExternalLink,
    FileDown,
    Info,
    Flag,
    LoaderCircle,
    MessageSquarePlus,
    Plus,
    Share2,
} from '@lucide/vue';
import {
    useEventListener,
    useMediaQuery,
    useResizeObserver,
} from '@vueuse/core';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import ReportDialog from '@/components/ReportDialog.vue';
import ArgumentMap from '@/components/submission/ArgumentMap.vue';
import FindingItem from '@/components/submission/FindingItem.vue';
import FindingsMargin from '@/components/submission/FindingsMargin.vue';
import HealthSummary from '@/components/submission/HealthSummary.vue';
import NoteComposer from '@/components/submission/NoteComposer.vue';
import ReadingView from '@/components/submission/ReadingView.vue';
import SubmissionStateBadge from '@/components/submission/SubmissionStateBadge.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Kbd } from '@/components/ui/kbd';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { provideReportDialog } from '@/composables/useReportDialog';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AspectKey } from '@/lib/analysis';
import { analysisTypes, aspects } from '@/lib/analysis';
import {
    buildFindings,
    buildParagraphs,
    computeHealth,
    findingOrder,
} from '@/lib/findings';
import { index as submissions, show } from '@/routes/submissions';
import type {
    ArgumentMap as ArgumentMapData,
    BreadcrumbItem,
    EthicsValue,
    Submission,
    SubmissionAnalysis,
    UserNote,
} from '@/types';

const props = defineProps<{
    submission: Submission;
    body: string;
    argumentMap: ArgumentMapData | null;
    analyses: SubmissionAnalysis[];
    userNotes: UserNote[];
}>();

defineOptions({
    layout: AppLayout,
});

setLayoutProps({
    breadcrumbs: [
        {
            title: props.submission.is_owner ? 'My analyses' : 'Community feed',
            href: props.submission.is_owner
                ? submissions()
                : submissions({ query: { scope: 'community' } }),
        },
        {
            title: `Analysis #${props.submission.id}`,
            href: show(props.submission.id),
        },
    ] satisfies BreadcrumbItem[],
});

const { open, reportableType, reportableId, report } = provideReportDialog();

const { start, stop } = usePoll(
    2500,
    { only: ['submission', 'analyses', 'argumentMap'] },
    { autoStart: false },
);

watch(
    () => props.submission.is_in_progress,
    (inProgress) => (inProgress ? start() : stop()),
    { immediate: true },
);

/* Findings and filters */

const enabled = ref<Record<AspectKey, boolean>>({
    fallacy: true,
    rhetoric: true,
    questions: true,
    community: true,
});
const showMinor = ref(false);
const activeKey = ref<string | null>(null);

const findings = computed(() =>
    buildFindings(props.body, props.analyses, props.userNotes),
);

const visibleFindings = computed(() =>
    findings.value.filter(
        (finding) =>
            enabled.value[finding.type] &&
            (showMinor.value || !finding.isMinor),
    ),
);

const hiddenMinorCount = computed(() =>
    showMinor.value
        ? 0
        : findings.value.filter(
              (finding) => enabled.value[finding.type] && finding.isMinor,
          ).length,
);

const paragraphs = computed(() =>
    buildParagraphs(props.body, visibleFindings.value),
);

const health = computed(() =>
    props.submission.state === 'analyzed'
        ? computeHealth(findings.value)
        : null,
);

const counts = computed(() => {
    const totals: Record<AspectKey, number> = {
        fallacy: 0,
        rhetoric: 0,
        questions: 0,
        community: 0,
    };

    for (const finding of findings.value) {
        totals[finding.type]++;
    }

    return totals;
});

const ethicsCounts = computed(() => {
    const totals: Record<EthicsValue, number> = {
        neutral: 0,
        questionable: 0,
        unethical: 0,
    };

    for (const finding of findings.value) {
        if (finding.ethics) {
            totals[finding.ethics]++;
        }
    }

    return totals;
});

const isArgument = computed(
    () =>
        props.submission.kind === null || props.submission.kind === 'argument',
);

const unavailable = computed<AspectKey[]>(() =>
    analysisTypes.filter(
        (type) =>
            props.analyses.find((analysis) => analysis.type === type)
                ?.status !== 'completed',
    ),
);

watch(visibleFindings, (visible) => {
    if (activeKey.value && !visible.some((f) => f.key === activeKey.value)) {
        activeKey.value = null;
    }
});

/* Margin layout */

const isWide = useMediaQuery('(min-width: 1024px)');
const reading = ref<InstanceType<typeof ReadingView> | null>(null);
const marginColumn = ref<HTMLElement | null>(null);
const articleColumn = ref<HTMLElement | null>(null);
const anchors = ref<Record<string, number | null>>({});

/** Anchors are measured from the top of the article, which starts level with the margin. */
function offsetFromArticle(top: number): number {
    return top - (articleColumn.value?.getBoundingClientRect().top ?? 0);
}

async function measureAnchors(): Promise<void> {
    await nextTick();

    const root = reading.value?.root;

    if (!root) {
        return;
    }

    const rootTop = root.getBoundingClientRect().top;
    const next: Record<string, number | null> = {};

    for (const finding of visibleFindings.value) {
        const top = reading.value?.anchorTop(finding.key) ?? null;
        next[finding.key] =
            top === null ? null : offsetFromArticle(rootTop + top) - 6;
    }

    anchors.value = next;
}

watch([paragraphs, isWide], measureAnchors);
useResizeObserver(articleColumn, () => void measureAnchors());
onMounted(() => {
    void document.fonts?.ready.then(() => measureAnchors());
    void measureAnchors();
});

/*
 * On wide screens the margin is a sticky column with its own scroll. It
 * follows the page so findings stay level with their passages, plus an
 * offset (`marginOffset`) that brings the active finding next to its
 * passage when findings above it were pushed down for lack of room.
 */
const marginOffset = ref(0);
/** Final (post-transition) position of each margin item, reported by FindingsMargin. */
const itemTops = ref<Record<string, number>>({});
let syncing = false;
let syncTimer: ReturnType<typeof setTimeout> | undefined;

/** How far the article has scrolled past the top of the (sticky) margin. */
function followedScroll(): number {
    const articleTop = articleColumn.value?.getBoundingClientRect().top ?? 0;
    const marginTop = marginColumn.value?.getBoundingClientRect().top ?? 0;

    return Math.max(0, marginTop - articleTop);
}

function syncMargin(behavior: ScrollBehavior = 'auto'): void {
    const scroller = marginColumn.value;

    if (!isWide.value || !scroller) {
        return;
    }

    syncing = true;
    clearTimeout(syncTimer);
    scroller.scrollTo({
        top: followedScroll() + marginOffset.value,
        behavior,
    });
    syncTimer = setTimeout(
        () => (syncing = false),
        behavior === 'smooth' ? 450 : 50,
    );
}

useEventListener(window, 'scroll', () => syncMargin(), { passive: true });

/** Remembers manual scrolling of the margin as the new offset. */
function handleMarginScroll(): void {
    if (!syncing && marginColumn.value) {
        marginOffset.value = marginColumn.value.scrollTop - followedScroll();
    }
}

function waitForLayout(): Promise<void> {
    return new Promise((resolve) =>
        requestAnimationFrame(() => requestAnimationFrame(() => resolve())),
    );
}

async function alignActive(): Promise<void> {
    const key = activeKey.value;

    if (!isWide.value || !key) {
        return;
    }

    await nextTick();
    await waitForLayout();

    const scroller = marginColumn.value;
    const mark = reading.value?.root?.querySelector(
        `[data-keys~="${CSS.escape(key)}"]`,
    );
    const target = itemTops.value[key];

    if (!scroller || !mark || target === undefined) {
        return;
    }

    // Where the item will sit once its move transition finishes, in viewport coordinates.
    const contentTop =
        scroller.getBoundingClientRect().top - scroller.scrollTop;
    const delta = contentTop + target - mark.getBoundingClientRect().top;

    marginOffset.value = Math.max(
        0,
        scroller.scrollTop + delta - followedScroll(),
    );
    syncMargin('smooth');
}

async function selectFinding(key: string, scrollText = false): Promise<void> {
    activeKey.value = key;

    if (scrollText) {
        await nextTick();
        reading.value?.scrollTo(key);
    }

    await alignActive();
}

/* On narrow screens the findings sit below the article, so a tapped passage opens in a sheet. */

const sheetOpen = ref(false);
const activeFinding = computed(
    () =>
        visibleFindings.value.find(
            (finding) => finding.key === activeKey.value,
        ) ?? null,
);
const activeIndex = computed(() =>
    visibleFindings.value.findIndex(
        (finding) => finding.key === activeKey.value,
    ),
);

function selectFromText(key: string): void {
    void selectFinding(key);

    if (!isWide.value) {
        sheetOpen.value = true;
    }
}

function stepSheet(step: 1 | -1): void {
    const list = visibleFindings.value;
    const next = (activeIndex.value + step + list.length) % list.length;

    void selectFinding(list[next].key, true);
}

watch(isWide, (wide) => {
    if (wide) {
        sheetOpen.value = false;
    }
});

watch(activeFinding, (finding) => {
    if (!finding) {
        sheetOpen.value = false;
    }
});

/* Community notes */

const composer = ref<{ label: string; anchor: number | null } | null>(null);
const selectionButton = ref<{
    top: number;
    left: number;
    label: string;
} | null>(null);

function handleSelection(): void {
    const selection = window.getSelection();
    const text = selection?.toString().trim() ?? '';
    const root = reading.value?.root;

    if (
        !selection ||
        text === '' ||
        !root ||
        !root.contains(selection.anchorNode)
    ) {
        selectionButton.value = null;

        return;
    }

    const rect = selection.getRangeAt(0).getBoundingClientRect();
    const bounds = articleColumn.value?.getBoundingClientRect();

    selectionButton.value = {
        top: rect.bottom - (bounds?.top ?? 0) + 8,
        left: rect.left - (bounds?.left ?? 0) + rect.width / 2,
        label: text.slice(0, 1000),
    };
}

function startNote(label: string, anchorTop: number | null): void {
    enabled.value = { ...enabled.value, community: true };
    activeKey.value = null;
    composer.value = { label, anchor: anchorTop };
    selectionButton.value = null;
    window.getSelection()?.removeAllRanges();
    marginOffset.value = 0;
    syncMargin('smooth');
}

function startNoteFromSelection(): void {
    const button = selectionButton.value;
    const selection = window.getSelection();

    if (!button) {
        return;
    }

    const rangeTop =
        selection && selection.rangeCount > 0
            ? selection.getRangeAt(0).getBoundingClientRect().top
            : null;

    startNote(
        button.label,
        isWide.value && rangeTop !== null
            ? offsetFromArticle(rangeTop) - 6
            : null,
    );
}

/* Header actions */

async function share(): Promise<void> {
    try {
        await navigator.clipboard.writeText(window.location.href);
        toast.success('Link copied to clipboard.');
    } catch {
        toast.error('Could not copy the link.');
    }
}

function exportPdf(): void {
    window.print();
}

/* Keyboard shortcuts: J/K steps through findings, 1–4 toggles categories. */

useEventListener(document, 'keydown', (event: KeyboardEvent) => {
    const target = event.target instanceof Element ? event.target : null;

    if (
        event.metaKey ||
        event.ctrlKey ||
        event.altKey ||
        target?.closest('input, textarea, select, [contenteditable="true"]')
    ) {
        return;
    }

    const list = visibleFindings.value;
    const index = list.findIndex((finding) => finding.key === activeKey.value);

    if ((event.key === 'j' || event.key === 'k') && list.length > 0) {
        event.preventDefault();
        const step = event.key === 'j' ? 1 : -1;
        const next =
            index === -1
                ? step === 1
                    ? 0
                    : list.length - 1
                : (index + step + list.length) % list.length;
        void selectFinding(list[next].key, true);
    } else if (['1', '2', '3', '4'].includes(event.key)) {
        const type = findingOrder[Number(event.key) - 1];

        if (!unavailable.value.includes(type)) {
            enabled.value = { ...enabled.value, [type]: !enabled.value[type] };
        }
    } else if (event.key === 'Escape') {
        activeKey.value = null;
        composer.value = null;
    }
});

const progressSteps = computed(() =>
    analysisTypes.map((type) => ({
        ...aspects[type],
        status:
            props.analyses.find((analysis) => analysis.type === type)?.status ??
            'not_started',
    })),
);
</script>

<template>
    <Head :title="submission.title" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-7 px-4 py-7 sm:px-6">
        <header class="grid gap-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div
                    class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                >
                    <SubmissionStateBadge
                        :state="submission.state"
                        :label="submission.state_label"
                        class="border-0"
                    />
                    <span>{{
                        submission.is_owner
                            ? 'by you'
                            : `by ${submission.author.name}`
                    }}</span>
                    <span aria-hidden="true">·</span>
                    <time :datetime="submission.created_at">{{
                        submission.created_at_human
                    }}</time>
                    <span aria-hidden="true">·</span>
                    <span class="font-mono text-[11px]"
                        >{{ submission.word_count }} words</span
                    >
                </div>
                <div
                    class="flex shrink-0 items-center gap-1.5"
                    data-print="hide"
                >
                    <Button
                        variant="secondary"
                        size="sm"
                        class="bg-surface"
                        @click="share"
                    >
                        <Share2 />
                        Share
                    </Button>
                    <Button
                        variant="secondary"
                        size="sm"
                        class="bg-surface"
                        :disabled="submission.state !== 'analyzed'"
                        @click="exportPdf"
                    >
                        <FileDown />
                        Export PDF
                    </Button>
                    <Button
                        v-if="!submission.is_owner"
                        variant="ghost"
                        size="sm"
                        class="text-muted-foreground hover:text-destructive"
                        @click="report('submission', submission.id)"
                    >
                        <Flag />
                        Report
                    </Button>
                </div>
            </div>
            <h1
                lang="hy"
                class="font-display text-xl leading-snug text-pretty sm:text-2xl"
            >
                {{ submission.title }}
            </h1>
            <a
                v-if="submission.source"
                :href="submission.source"
                target="_blank"
                rel="noopener noreferrer nofollow"
                class="-mt-1 inline-flex items-center gap-1 self-start text-sm text-brand-strong hover:underline"
            >
                {{ submission.source_host ?? submission.source }}
                <ExternalLink class="size-3.5" />
            </a>
        </header>

        <section
            v-if="submission.is_in_progress"
            class="rounded-2xl bg-surface p-5"
            aria-live="polite"
        >
            <div class="flex items-center gap-3">
                <LoaderCircle class="size-5 animate-spin text-brand-strong" />
                <div>
                    <p class="font-medium">Analyzing your text…</p>
                    <p class="text-sm text-muted-foreground">
                        This usually takes under a minute. You can leave this
                        page; we'll keep working.
                    </p>
                </div>
            </div>
            <ol class="mt-4 grid gap-2 sm:grid-cols-3">
                <li
                    v-for="step in progressSteps"
                    :key="step.key"
                    class="flex items-center gap-3 rounded-xl bg-background px-4 py-3"
                >
                    <component
                        :is="step.icon"
                        class="size-4"
                        :class="step.textClass"
                    />
                    <span class="flex-1 text-sm">{{ step.label }}</span>
                    <CircleCheck
                        v-if="step.status === 'completed'"
                        class="size-4 text-brand-strong"
                    />
                    <CircleAlert
                        v-else-if="step.status === 'failed'"
                        class="size-4 text-destructive"
                    />
                    <LoaderCircle
                        v-else
                        class="size-4 animate-spin text-muted-foreground"
                    />
                </li>
            </ol>
        </section>

        <Alert
            v-if="submission.state === 'failed'"
            variant="destructive"
            class="border-0 bg-destructive/8"
        >
            <CircleAlert />
            <AlertTitle>We couldn't analyze this text</AlertTitle>
            <AlertDescription>
                Something went wrong on our side. The community can still add
                notes to it.
            </AlertDescription>
        </Alert>

        <Alert
            v-if="submission.state === 'analyzed' && !isArgument"
            class="border-0 bg-surface"
        >
            <Info />
            <AlertTitle>No argument to analyze</AlertTitle>
            <AlertDescription>
                This looks like {{ submission.kind_label?.toLowerCase() }}
                rather than an argument about a public matter, so there are no
                reasoning gaps or questions to show. Community notes are still
                open.
            </AlertDescription>
        </Alert>

        <HealthSummary
            v-if="isArgument"
            v-model:enabled="enabled"
            v-model:show-minor="showMinor"
            :health="health"
            :counts="counts"
            :unavailable="unavailable"
            :ethics-counts="ethicsCounts"
        />

        <ArgumentMap
            v-if="argumentMap && isArgument && argumentMap.claims.length > 0"
            :map="argumentMap"
        />

        <section
            class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_21rem] lg:gap-10"
        >
            <div
                ref="articleColumn"
                class="relative min-w-0"
                @mouseup="handleSelection"
            >
                <div
                    class="mb-5 flex items-center justify-between gap-3 text-xs text-muted-foreground"
                    data-print="hide"
                >
                    <span class="flex items-center gap-1.5">
                        <MessageSquarePlus class="size-3.5" />
                        Select any passage to add a community note
                    </span>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="h-7 text-xs"
                        @click="startNote('', isWide ? 0 : null)"
                    >
                        <Plus />
                        General note
                    </Button>
                </div>

                <ReadingView
                    ref="reading"
                    :paragraphs="paragraphs"
                    :active-key="activeKey"
                    @select="selectFromText"
                />

                <Button
                    v-if="selectionButton"
                    type="button"
                    size="sm"
                    class="absolute z-20 -translate-x-1/2 shadow-lg"
                    :style="{
                        top: `${selectionButton.top}px`,
                        left: `${selectionButton.left}px`,
                    }"
                    @mouseup.stop
                    @click="startNoteFromSelection"
                >
                    <Plus />
                    Add note
                </Button>
            </div>

            <aside
                ref="marginColumn"
                aria-label="Findings"
                class="lg:sticky lg:top-20 lg:max-h-[calc(100svh-6rem)] lg:[scrollbar-width:thin] lg:self-start lg:overflow-y-auto lg:overscroll-contain"
                @scroll="handleMarginScroll"
            >
                <FindingsMargin
                    :findings="visibleFindings"
                    :active-key="activeKey"
                    :anchors="anchors"
                    :composer-anchor="composer ? (composer.anchor ?? 0) : null"
                    :floating="isWide"
                    :hidden-minor-count="hiddenMinorCount"
                    @select="(key) => selectFinding(key, true)"
                    @show-minor="showMinor = true"
                    @layout="(tops) => (itemTops = tops)"
                >
                    <template v-if="composer" #composer>
                        <NoteComposer
                            :submission-id="submission.id"
                            :label="composer.label"
                            @close="composer = null"
                        />
                    </template>
                </FindingsMargin>

                <p
                    v-if="visibleFindings.length === 0 && !composer"
                    class="rounded-xl bg-surface px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    {{
                        submission.is_in_progress
                            ? 'Findings will appear here as soon as the analysis finishes.'
                            : 'No findings in the selected categories.'
                    }}
                </p>
            </aside>
        </section>

        <footer
            class="hidden flex-wrap items-center gap-4 text-xs text-muted-foreground lg:flex"
            data-print="hide"
        >
            <span class="flex items-center gap-1"
                ><Kbd>J</Kbd><Kbd>K</Kbd> next / previous</span
            >
            <span class="flex items-center gap-1"
                ><Kbd>1</Kbd>–<Kbd>4</Kbd> toggle categories</span
            >
            <span class="flex items-center gap-1"
                ><Kbd>Esc</Kbd> clear selection</span
            >
        </footer>
    </div>

    <Sheet v-model:open="sheetOpen">
        <SheetContent
            side="bottom"
            class="max-h-[85svh] gap-0 overflow-y-auto rounded-t-2xl border-0 p-0"
            data-test="finding-sheet"
        >
            <SheetHeader
                class="sticky top-0 z-10 flex-row items-center gap-1 bg-background px-3 pt-3 pr-12 pb-2"
            >
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Previous finding"
                    :disabled="visibleFindings.length < 2"
                    @click="stepSheet(-1)"
                >
                    <ChevronLeft />
                </Button>
                <SheetTitle class="text-sm font-medium tabular-nums">
                    {{ activeIndex + 1 }} of {{ visibleFindings.length }}
                </SheetTitle>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Next finding"
                    :disabled="visibleFindings.length < 2"
                    @click="stepSheet(1)"
                >
                    <ChevronRight />
                </Button>
                <SheetDescription class="sr-only">
                    The finding for the passage you tapped
                </SheetDescription>
            </SheetHeader>
            <div class="px-2 pb-6">
                <FindingItem
                    v-if="activeFinding"
                    :key="activeFinding.key"
                    :finding="activeFinding"
                    active
                />
            </div>
        </SheetContent>
    </Sheet>

    <ReportDialog
        v-model:open="open"
        :reportable-type="reportableType"
        :reportable-id="reportableId"
    />
</template>
