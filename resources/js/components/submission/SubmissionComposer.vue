<script setup lang="ts">
import { useForm, useHttp, usePage } from '@inertiajs/vue3';
import { Link2, ScanSearch, Trash2, Type } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import DuplicateMatches from '@/components/submission/DuplicateMatches.vue';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Kbd } from '@/components/ui/kbd';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import UsageLimitDialog from '@/components/UsageLimitDialog.vue';
import { armenianWordCount } from '@/lib/analysis';
import { store as checkDuplicates } from '@/routes/submission-duplicate-checks';
import { store as storeSubmission } from '@/routes/submissions';
import { store as storeUrlSubmission } from '@/routes/url-submissions';
import type { DuplicateMatch } from '@/types';

const props = defineProps<{
    sampleText: string;
    wordLimits: { min: number; max: number };
}>();

type DuplicateResponse = { duplicates: DuplicateMatch[] };

const page = usePage();
const usage = computed(() => page.props.auth.usage);
const hasAllowance = computed(
    () => (usage.value?.submissions.remaining ?? 0) > 0,
);

const mode = ref<'text' | 'url'>('url');
const limitDialogOpen = ref(false);

const textForm = useForm({ body: '' });
const urlForm = useForm({ url: '' });

const duplicateCheck = useHttp<
    { text?: string; url?: string },
    DuplicateResponse
>({});
const textDuplicates = ref<DuplicateMatch[]>([]);
const urlDuplicates = ref<DuplicateMatch[]>([]);

const wordCount = computed(() => armenianWordCount(textForm.body));
const wordCountState = computed(() => {
    if (wordCount.value === 0) {
        return 'empty';
    }

    if (wordCount.value < props.wordLimits.min) {
        return 'short';
    }

    return wordCount.value > props.wordLimits.max ? 'long' : 'ok';
});

const canSubmitText = computed(
    () => wordCountState.value === 'ok' && !textForm.processing,
);

const isValidUrl = computed(() => {
    try {
        const url = new URL(urlForm.url);

        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch {
        return false;
    }
});

function guardAllowance(): boolean {
    if (!hasAllowance.value) {
        limitDialogOpen.value = true;

        return false;
    }

    return true;
}

function handleLimitError(errors: Record<string, string>): void {
    if (errors.limit) {
        limitDialogOpen.value = true;
    }
}

async function findDuplicates(
    payload: { text: string } | { url: string },
): Promise<DuplicateMatch[]> {
    try {
        duplicateCheck.transform(() => payload);
        const response = await duplicateCheck.post(checkDuplicates().url);

        return response.duplicates;
    } catch {
        return [];
    }
}

async function submitText(force = false): Promise<void> {
    if (!guardAllowance() || !canSubmitText.value) {
        return;
    }

    if (!force) {
        textDuplicates.value = await findDuplicates({ text: textForm.body });

        if (textDuplicates.value.length > 0) {
            return;
        }
    }

    textForm.submit(storeSubmission(), {
        onError: handleLimitError,
        onSuccess: () => {
            textForm.reset();
            textDuplicates.value = [];
        },
    });
}

function submitUrl(force = false): void {
    if (!guardAllowance() || !isValidUrl.value) {
        return;
    }

    if (!force && urlDuplicates.value.length > 0) {
        return;
    }

    urlForm.submit(storeUrlSubmission(), {
        onError: handleLimitError,
        onSuccess: () => {
            urlForm.reset();
            urlDuplicates.value = [];
        },
    });
}

const checkUrlDuplicates = useDebounceFn(async () => {
    urlDuplicates.value = isValidUrl.value
        ? await findDuplicates({ url: urlForm.url })
        : [];
}, 450);

watch(
    () => urlForm.url,
    () => {
        urlDuplicates.value = [];
        void checkUrlDuplicates();
    },
);

watch(
    () => textForm.body,
    () => {
        textDuplicates.value = [];
    },
);

function useSample(): void {
    textForm.body = props.sampleText;
    mode.value = 'text';
}

function handleKeydown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
        event.preventDefault();
        void submitText();
    }
}
</script>

<template>
    <div class="overflow-hidden rounded-xl bg-surface">
        <Tabs v-model="mode" class="gap-0">
            <div
                class="flex flex-wrap items-center justify-between gap-3 px-4 pt-4 sm:px-5"
            >
                <TabsList class="h-9 bg-surface-strong">
                    <TabsTrigger value="url" class="px-3">
                        <Link2 />
                        URL
                    </TabsTrigger>
                    <TabsTrigger value="text" class="px-3">
                        <Type />
                        Text
                    </TabsTrigger>
                </TabsList>
                <p class="text-xs text-muted-foreground">
                    1 analysis per submission
                    <template v-if="usage">
                        ·
                        <span class="font-medium text-foreground tabular-nums"
                            >{{ usage.submissions.remaining }} left today</span
                        >
                    </template>
                </p>
            </div>

            <TabsContent value="text" class="m-0">
                <form
                    class="grid gap-3 p-4 sm:p-5"
                    @submit.prevent="submitText()"
                >
                    <div
                        class="group/editor relative rounded-lg bg-background shadow-xs transition-shadow focus-within:ring-[3px] focus-within:ring-ring/30"
                    >
                        <Textarea
                            v-model="textForm.body"
                            name="body"
                            lang="hy"
                            aria-label="Text to analyze"
                            :placeholder="`Paste an Armenian statement, speech or article (${wordLimits.min}–${wordLimits.max} words) to check it for fallacies, rhetoric and open questions.`"
                            class="field-sizing-fixed min-h-64 resize-none border-0 bg-transparent px-4 py-3.5 text-base leading-7 shadow-none focus-visible:ring-0 md:text-[0.95rem] dark:bg-transparent"
                            @keydown="handleKeydown"
                        />
                        <div
                            class="flex items-center justify-between gap-2 px-3 pb-3"
                        >
                            <span
                                class="rounded-md px-2 py-1 text-xs tabular-nums transition-colors"
                                :class="{
                                    'text-muted-foreground':
                                        wordCountState === 'empty',
                                    'bg-questions-soft text-questions':
                                        wordCountState === 'short' ||
                                        wordCountState === 'long',
                                    'bg-brand-soft text-brand-strong':
                                        wordCountState === 'ok',
                                }"
                                data-test="word-count"
                            >
                                {{ wordCount }} / {{ wordLimits.min }}–{{
                                    wordLimits.max
                                }}
                                words
                            </span>
                            <Button
                                v-if="textForm.body"
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                aria-label="Clear text"
                                @click="textForm.reset()"
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    </div>
                    <InputError :message="textForm.errors.body" />

                    <DuplicateMatches
                        v-if="textDuplicates.length > 0"
                        :duplicates="textDuplicates"
                        :processing="textForm.processing"
                        @proceed="submitText(true)"
                        @dismiss="textDuplicates = []"
                    />

                    <div
                        v-else
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <button
                            type="button"
                            class="text-sm text-muted-foreground underline-offset-4 transition-colors hover:text-foreground hover:underline"
                            @click="useSample"
                        >
                            Try a sample text →
                        </button>
                        <div class="flex items-center gap-3">
                            <span
                                class="hidden items-center gap-1 text-xs text-muted-foreground md:flex"
                            >
                                <Kbd>⌘</Kbd><Kbd>Enter</Kbd>
                            </span>
                            <Button
                                type="submit"
                                size="lg"
                                :disabled="
                                    !canSubmitText || duplicateCheck.processing
                                "
                                data-test="analyze-text-button"
                            >
                                <Spinner
                                    v-if="
                                        textForm.processing ||
                                        duplicateCheck.processing
                                    "
                                />
                                <ScanSearch v-else />
                                {{
                                    duplicateCheck.processing
                                        ? 'Checking…'
                                        : 'Analyze text'
                                }}
                            </Button>
                        </div>
                    </div>
                </form>
            </TabsContent>

            <TabsContent value="url" class="m-0">
                <form
                    class="grid gap-3 p-4 sm:p-5"
                    @submit.prevent="submitUrl()"
                >
                    <p class="text-sm text-muted-foreground">
                        Paste a link to an Armenian article. We'll fetch the
                        text and analyze it for you.
                    </p>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <InputGroup
                            class="h-14 flex-1 border-2 border-input bg-background shadow-xs has-[[data-slot=input-group-control]:focus-visible]:border-brand dark:bg-background"
                        >
                            <InputGroupAddon class="pl-4">
                                <Link2 class="size-5" />
                            </InputGroupAddon>
                            <InputGroupInput
                                v-model="urlForm.url"
                                name="url"
                                type="url"
                                inputmode="url"
                                placeholder="https://example.am/article"
                                aria-label="Article URL"
                                class="text-base md:text-lg"
                            />
                            <InputGroupAddon
                                v-if="duplicateCheck.processing"
                                align="inline-end"
                            >
                                <Spinner />
                            </InputGroupAddon>
                        </InputGroup>
                        <Button
                            v-if="urlDuplicates.length === 0"
                            type="submit"
                            class="h-14 px-6 text-base"
                            :disabled="!isValidUrl || urlForm.processing"
                            data-test="analyze-url-button"
                        >
                            <Spinner v-if="urlForm.processing" />
                            <ScanSearch v-else />
                            Fetch &amp; analyze
                        </Button>
                    </div>
                    <InputError :message="urlForm.errors.url" />

                    <DuplicateMatches
                        v-if="urlDuplicates.length > 0"
                        :duplicates="urlDuplicates"
                        :processing="urlForm.processing"
                        @proceed="submitUrl(true)"
                        @dismiss="urlForm.reset()"
                    />
                </form>
            </TabsContent>
        </Tabs>

        <UsageLimitDialog v-model:open="limitDialogOpen" />
    </div>
</template>
