import type { LucideIcon } from '@lucide/vue';
import {
    CircleAlert,
    CircleCheck,
    CircleHelp,
    Clock,
    Feather,
    LoaderCircle,
    MessagesSquare,
    Puzzle,
} from '@lucide/vue';
import type {
    AnalysisTypeValue,
    EthicsValue,
    SubmissionStateValue,
} from '@/types';

export type AspectKey = AnalysisTypeValue | 'community';

export type AspectMeta = {
    key: AspectKey;
    label: string;
    armenianLabel: string;
    singular: string;
    description: string;
    icon: LucideIcon;
    /** CSS custom property holding the aspect's accent color. */
    color: string;
    textClass: string;
    softClass: string;
};

export const aspects: Record<AspectKey, AspectMeta> = {
    community: {
        key: 'community',
        label: 'Community notes',
        armenianLabel: 'Համայնքի նշումներ',
        singular: 'note',
        description: 'Highlight any passage to add context for other readers.',
        icon: MessagesSquare,
        color: 'var(--community)',
        textClass: 'text-community',
        softClass: 'bg-community-soft',
    },
    fallacy: {
        key: 'fallacy',
        label: 'Reasoning gaps',
        armenianLabel: 'Տրամաբանական բացեր',
        singular: 'gap',
        description:
            'Steps in the argument that do not follow from its reasons.',
        icon: Puzzle,
        color: 'var(--fallacy)',
        textClass: 'text-fallacy',
        softClass: 'bg-fallacy-soft',
    },
    rhetoric: {
        key: 'rhetoric',
        label: 'Rhetoric',
        armenianLabel: 'Հռետորական հնարքներ',
        singular: 'device',
        description: 'Persuasive devices used to shape how a message lands.',
        icon: Feather,
        color: 'var(--rhetoric)',
        textClass: 'text-rhetoric',
        softClass: 'bg-rhetoric-soft',
    },
    questions: {
        key: 'questions',
        label: 'Open questions',
        armenianLabel: 'Բաց հարցեր',
        singular: 'question',
        description: 'What evidence or public judgment should settle.',
        icon: CircleHelp,
        color: 'var(--questions)',
        textClass: 'text-questions',
        softClass: 'bg-questions-soft',
    },
};

export const analysisTypes: AnalysisTypeValue[] = [
    'fallacy',
    'rhetoric',
    'questions',
];

export type EthicsMeta = {
    label: string;
    color: string;
    textClass: string;
};

export const ethics: Record<EthicsValue, EthicsMeta> = {
    neutral: {
        label: 'Ethically neutral',
        color: 'var(--ethics-neutral)',
        textClass: 'text-ethics-neutral',
    },
    questionable: {
        label: 'Questionable use',
        color: 'var(--ethics-questionable)',
        textClass: 'text-ethics-questionable',
    },
    unethical: {
        label: 'Unethical use',
        color: 'var(--ethics-unethical)',
        textClass: 'text-ethics-unethical',
    },
};

export type StateMeta = {
    icon: LucideIcon;
    spin: boolean;
    class: string;
};

export const states: Record<SubmissionStateValue, StateMeta> = {
    pending: {
        icon: Clock,
        spin: false,
        class: 'border-questions/30 bg-questions-soft text-questions',
    },
    processing: {
        icon: LoaderCircle,
        spin: true,
        class: 'border-rhetoric/30 bg-rhetoric-soft text-rhetoric',
    },
    analyzed: {
        icon: CircleCheck,
        spin: false,
        class: 'border-brand/40 bg-brand-soft text-brand-strong',
    },
    failed: {
        icon: CircleAlert,
        spin: false,
        class: 'border-destructive/30 bg-destructive/10 text-destructive',
    },
};

/** Escape text so it can be safely interpolated into an HTML string. */
export function escapeHtml(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

/** Count Armenian words the same way the server-side validation rule does. */
export function armenianWordCount(value: string): number {
    return value.match(/[԰-֏ﬓ-ﬗ]{2,}/gu)?.length ?? 0;
}
