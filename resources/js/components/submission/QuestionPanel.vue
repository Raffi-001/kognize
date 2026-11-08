<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import {
    CircleAlert,
    CircleCheck,
    ExternalLink,
    LoaderCircle,
    Plus,
    Scale,
} from '@lucide/vue';
import { ref } from 'vue';
import AuthorLink from '@/components/reputation/AuthorLink.vue';
import VoteControl from '@/components/reputation/VoteControl.vue';
import EvidenceForm from '@/components/submission/EvidenceForm.vue';
import { Button } from '@/components/ui/button';
import type { FindingQuestion } from '@/lib/findings';
import { cn } from '@/lib/utils';
import type { Evidence, VerdictValue } from '@/types';

defineProps<{
    questionId: number;
    question: FindingQuestion;
}>();

const adding = ref(false);
const page = usePage();

const verdictClass: Record<VerdictValue, string> = {
    supported: 'bg-brand-soft text-brand-strong',
    refuted: 'bg-destructive/10 text-destructive',
    contested: 'bg-questions-soft text-questions',
};

function verification(evidence: Evidence): {
    label: string;
    ok: boolean | null;
} {
    switch (evidence.verification) {
        case 'verified':
            return { label: 'Passage found on the page', ok: true };
        case 'not_found':
            return { label: 'Passage not found on the page', ok: false };
        case 'unreachable':
            return { label: 'Page could not be opened', ok: false };
        default:
            return { label: 'Checking the source…', ok: null };
    }
}

const assessmentLabel = {
    supports: 'Supports the claim',
    refutes: 'Refutes the claim',
    irrelevant: 'Does not address the claim',
} as const;
</script>

<template>
    <div class="relative mt-3 grid gap-2.5">
        <template v-if="question.kind === 'factual'">
            <div
                v-if="question.status === 'resolved' && question.verdict"
                :class="
                    cn('rounded-lg px-3 py-2.5', verdictClass[question.verdict])
                "
            >
                <p class="flex items-center gap-1.5 text-xs font-semibold">
                    <Scale class="size-3.5" />
                    {{ question.verdictLabel }}
                    <span class="font-normal opacity-80"
                        >· resolved {{ question.resolvedAtHuman }}</span
                    >
                </p>
                <p
                    v-if="question.resolution"
                    lang="hy"
                    class="mt-1 text-[0.8rem] leading-relaxed text-foreground/85"
                >
                    {{ question.resolution }}
                </p>
            </div>

            <p v-else class="text-xs leading-relaxed text-muted-foreground">
                Open for evidence. Moderators resolve it once verified sources
                point one way.
            </p>

            <ul v-if="question.evidence.length > 0" class="grid gap-1.5">
                <li
                    v-for="evidence in question.evidence"
                    :key="evidence.id"
                    class="rounded-lg bg-background px-3 py-2 text-xs"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="font-medium"
                            :class="
                                evidence.stance === 'supports'
                                    ? 'text-brand-strong'
                                    : 'text-destructive'
                            "
                            >{{
                                evidence.stance === 'supports'
                                    ? 'Supports'
                                    : 'Refutes'
                            }}</span
                        >
                        <a
                            :href="evidence.url"
                            target="_blank"
                            rel="noopener noreferrer nofollow"
                            class="flex min-w-0 items-center gap-1 text-muted-foreground hover:text-foreground"
                        >
                            <span class="truncate">{{
                                evidence.page_title ?? evidence.source_host
                            }}</span>
                            <ExternalLink class="size-3 shrink-0" />
                        </a>
                    </div>
                    <p
                        lang="hy"
                        class="mt-1 line-clamp-3 text-foreground/80 italic"
                    >
                        “{{ evidence.quote }}”
                    </p>
                    <p
                        class="mt-1.5 flex items-center gap-1"
                        :class="{
                            'text-brand-strong':
                                verification(evidence).ok === true,
                            'text-destructive':
                                verification(evidence).ok === false,
                            'text-muted-foreground':
                                verification(evidence).ok === null,
                        }"
                    >
                        <CircleCheck
                            v-if="verification(evidence).ok === true"
                            class="size-3"
                        />
                        <CircleAlert
                            v-else-if="verification(evidence).ok === false"
                            class="size-3"
                        />
                        <LoaderCircle v-else class="size-3 animate-spin" />
                        {{ verification(evidence).label }}
                        <template v-if="evidence.assessment">
                            · {{ assessmentLabel[evidence.assessment] }}
                        </template>
                    </p>
                    <div
                        class="mt-1.5 flex items-center justify-between gap-2 text-muted-foreground"
                    >
                        <span class="flex items-center gap-1"
                            >Cited by <AuthorLink :author="evidence.author"
                        /></span>
                        <VoteControl
                            size="xs"
                            type="evidence"
                            :id="evidence.id"
                            :score="evidence.score"
                            :my-vote="evidence.my_vote"
                            :own="
                                evidence.author.id === page.props.auth.user?.id
                            "
                        />
                    </div>
                </li>
            </ul>

            <template v-if="question.status === 'open'">
                <EvidenceForm
                    v-if="adding"
                    :question-id="questionId"
                    @done="adding = false"
                />
                <Button
                    v-else
                    type="button"
                    variant="secondary"
                    size="sm"
                    class="justify-self-start bg-background"
                    @click="adding = true"
                >
                    <Plus />
                    Add evidence
                </Button>
            </template>
        </template>
    </div>
</template>
