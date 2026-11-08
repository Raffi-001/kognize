<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, Flag, MessageCircle } from '@lucide/vue';
import { computed } from 'vue';
import AuthorLink from '@/components/reputation/AuthorLink.vue';
import VoteControl from '@/components/reputation/VoteControl.vue';
import CommentThread from '@/components/submission/CommentThread.vue';
import QuestionPanel from '@/components/submission/QuestionPanel.vue';
import QuotedPassage from '@/components/submission/QuotedPassage.vue';
import { Button } from '@/components/ui/button';
import { useReportDialog } from '@/composables/useReportDialog';
import { aspects, ethics } from '@/lib/analysis';
import type { Finding } from '@/lib/findings';
import { lookupGlossary } from '@/lib/findings';
import { cn } from '@/lib/utils';
import { store as storeAnalysisNoteComment } from '@/routes/analysis-notes/comments';
import { show as showQuestion } from '@/routes/questions';
import { store as storeUserNoteComment } from '@/routes/user-notes/comments';

const props = defineProps<{
    finding: Finding;
    active: boolean;
    /** Another finding is active, so this one steps back. */
    dimmed?: boolean;
}>();

defineEmits<{
    select: [];
}>();

const { report } = useReportDialog();

const aspect = computed(() => aspects[props.finding.type]);
const ethicsMeta = computed(() =>
    props.finding.ethics ? ethics[props.finding.ethics] : null,
);
const isCommunity = computed(() => props.finding.type === 'community');
const tagLabels: Record<Finding['type'], string> = {
    fallacy: 'Reasoning gap',
    rhetoric: 'Rhetoric',
    questions: 'Question',
    community: 'Community note',
};
const tag = computed(() => {
    return props.finding.question
        ? 'Factual question'
        : tagLabels[props.finding.type];
});
const questionChip = computed(() => {
    const question = props.finding.question;

    if (!question) {
        return null;
    }

    return question.status === 'resolved' && question.verdictLabel
        ? {
              label: question.verdictLabel,
              class: 'bg-brand-soft text-brand-strong',
          }
        : { label: 'Open', class: 'bg-questions-soft text-questions' };
});
const glossary = computed(() =>
    props.finding.type === 'fallacy' || props.finding.type === 'rhetoric'
        ? lookupGlossary(props.finding.title)
        : null,
);
const glossaryQuestion = computed(() => {
    const name = glossary.value?.name.toLowerCase() ?? '';

    return `What is ${/^[aeiou]/.test(name) ? 'an' : 'a'} ${name}?`;
});
const wikiUrl = computed(() =>
    props.finding.type === 'fallacy' &&
    !glossary.value &&
    props.finding.title !== 'Reasoning gap'
        ? `https://fallacy.wiki/${encodeURIComponent(
              props.finding.title.toLowerCase().replaceAll(/\s+/g, '-'),
          )}`
        : null,
);
const commentAction = computed(() =>
    isCommunity.value
        ? storeUserNoteComment.form(props.finding.id)
        : storeAnalysisNoteComment.form(props.finding.id),
);
const canReport = computed(() => !isCommunity.value || !props.finding.isOwner);
</script>

<template>
    <article
        :class="
            cn(
                'group/finding relative rounded-lg px-3 py-2.5 transition-[background-color,opacity,box-shadow] duration-200',
                active
                    ? 'bg-surface-strong shadow-sm ring-1 ring-foreground/5'
                    : 'hover:bg-surface',
                dimmed && 'opacity-55 hover:opacity-100',
            )
        "
        :data-active="active"
        data-test="finding"
    >
        <span
            v-if="active"
            aria-hidden="true"
            class="absolute inset-y-3 left-0 w-[3px] rounded-full"
            :style="{ backgroundColor: aspect.color }"
        />
        <button
            v-if="!active"
            type="button"
            class="absolute inset-0 rounded-lg focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none"
            :aria-label="`Show ${tag}: ${finding.title}`"
            @click="$emit('select')"
        />

        <header class="flex items-center gap-2">
            <span
                class="text-[10.5px] font-semibold tracking-wider uppercase"
                :class="aspect.textClass"
                >{{ tag }}</span
            >
            <span
                v-if="questionChip"
                class="rounded-full px-1.5 py-px text-[10px] font-medium"
                :class="questionChip.class"
                >{{ questionChip.label }}</span
            >
            <div
                v-if="finding.strength !== null"
                class="ml-auto flex items-center gap-0.5"
                :title="`Strength ${finding.strength}/10`"
            >
                <span
                    v-for="step in 10"
                    :key="step"
                    class="h-2.5 w-1 rounded-full"
                    :class="step <= finding.strength ? '' : 'bg-surface-strong'"
                    :style="
                        step <= finding.strength
                            ? { backgroundColor: aspect.color }
                            : undefined
                    "
                />
            </div>
            <span
                v-else-if="finding.createdAtHuman"
                class="ml-auto text-xs text-muted-foreground"
                >{{ finding.createdAtHuman }}</span
            >
        </header>

        <h3
            class="mt-1 text-sm leading-snug font-semibold"
            :lang="isCommunity ? undefined : 'hy'"
        >
            <AuthorLink
                v-if="isCommunity && finding.author"
                :author="finding.author"
            />
            <template v-else>{{ finding.title }}</template>
            <span
                v-if="isCommunity && finding.isOwner"
                class="font-normal text-muted-foreground"
                >(you)</span
            >
        </h3>
        <p
            v-if="finding.subtitle && !isCommunity"
            lang="hy"
            class="text-xs text-muted-foreground"
        >
            {{ finding.subtitle }}
        </p>

        <QuotedPassage
            v-if="finding.label && finding.start === null"
            :color="aspect.color"
            class="mt-2"
        >
            {{ finding.label }}
        </QuotedPassage>

        <p
            v-if="active || isCommunity"
            lang="hy"
            :class="
                cn(
                    'mt-1.5 text-[0.8rem] leading-relaxed text-foreground/80',
                    !active && 'line-clamp-2',
                    isCommunity && 'whitespace-pre-wrap',
                )
            "
        >
            {{ finding.text }}
        </p>

        <div
            v-if="active && glossary"
            class="mt-2.5 rounded-lg bg-background px-3 py-2 text-xs leading-relaxed"
        >
            <span class="font-semibold">{{ glossaryQuestion }}</span>
            <span class="text-foreground/80"> {{ glossary.definition }}</span>
        </div>

        <footer
            class="relative mt-1.5 flex items-center justify-between gap-2 text-xs text-muted-foreground"
        >
            <span
                v-if="ethicsMeta"
                class="inline-flex items-center gap-1.5 font-medium"
                :class="ethicsMeta.textClass"
            >
                <span
                    class="size-1.5 rounded-full"
                    :style="{ backgroundColor: ethicsMeta.color }"
                />
                {{ ethicsMeta.label }}
            </span>
            <span v-else />

            <span class="flex items-center gap-0.5">
                <VoteControl
                    v-if="isCommunity"
                    class="relative mr-1"
                    type="user_note"
                    :id="finding.id"
                    :score="finding.score"
                    :my-vote="finding.myVote"
                    :own="finding.isOwner"
                />
                <Button
                    v-if="active && wikiUrl"
                    as="a"
                    :href="wikiUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    variant="ghost"
                    size="sm"
                    class="h-6 px-1.5 text-xs text-muted-foreground"
                >
                    <BookOpen />
                    Learn
                </Button>
                <span class="inline-flex items-center gap-1 px-1.5">
                    <MessageCircle class="size-3.5" />
                    {{ finding.comments.length }}
                </span>
                <Button
                    v-if="active && canReport"
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    class="size-6 text-muted-foreground hover:text-destructive"
                    :aria-label="`Report this ${tag.toLowerCase()}`"
                    @click="
                        report(
                            isCommunity ? 'user_note' : 'analysis_note',
                            finding.id,
                        )
                    "
                >
                    <Flag />
                </Button>
            </span>
        </footer>

        <template v-if="active && finding.question">
            <QuestionPanel
                :question-id="finding.id"
                :question="finding.question"
            />
            <Link
                :href="showQuestion(finding.id)"
                class="relative mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-strong hover:underline"
            >
                Open the question page
                <ArrowRight class="size-3" />
            </Link>
        </template>

        <CommentThread
            v-if="active"
            class="mt-3"
            :comments="finding.comments"
            :action="commentAction"
        />
    </article>
</template>
