import type { LucideIcon } from '@lucide/vue';
import {
    Award,
    Crown,
    FileSearch,
    Landmark,
    MessagesSquare,
    NotebookPen,
    ShieldCheck,
    Sparkles,
    Star,
    ThumbsDown,
    ThumbsUp,
    Users,
} from '@lucide/vue';

export type BadgeTierValue = 'gold' | 'silver' | 'bronze';

export const badgeTiers: Record<
    BadgeTierValue,
    { label: string; dot: string; soft: string }
> = {
    gold: {
        label: 'Gold',
        dot: 'bg-amber-400',
        soft: 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-900',
    },
    silver: {
        label: 'Silver',
        dot: 'bg-slate-400',
        soft: 'bg-slate-50 text-slate-700 ring-slate-200 dark:bg-slate-900/50 dark:text-slate-200 dark:ring-slate-800',
    },
    bronze: {
        label: 'Bronze',
        dot: 'bg-orange-700/70',
        soft: 'bg-orange-50 text-orange-900 ring-orange-200 dark:bg-orange-950/40 dark:text-orange-200 dark:ring-orange-900',
    },
};

export const tierOrder: BadgeTierValue[] = ['gold', 'silver', 'bronze'];

export type BadgeValue =
    | 'supporter'
    | 'critic'
    | 'annotator'
    | 'commentator'
    | 'researcher'
    | 'fact_checker'
    | 'recruiter'
    | 'good_note'
    | 'great_note'
    | 'excellent_note'
    | 'respected'
    | 'pillar';

export const badgeIcons: Record<BadgeValue, LucideIcon> = {
    supporter: ThumbsUp,
    critic: ThumbsDown,
    annotator: NotebookPen,
    commentator: MessagesSquare,
    researcher: FileSearch,
    fact_checker: ShieldCheck,
    recruiter: Users,
    good_note: Star,
    great_note: Sparkles,
    excellent_note: Crown,
    respected: Award,
    pillar: Landmark,
};

/** Metal colors for the SVG medals; they stay the same in light and dark mode. */
export const medalColors: Record<
    BadgeTierValue,
    { light: string; mid: string; rim: string; ribbon: string; glyph: string }
> = {
    gold: {
        light: '#FDE68A',
        mid: '#E3A92B',
        rim: '#B7791F',
        ribbon: '#2563EB',
        glyph: '#6B3F06',
    },
    silver: {
        light: '#F4F6F9',
        mid: '#AEB7C4',
        rim: '#7C8797',
        ribbon: '#64748B',
        glyph: '#374151',
    },
    bronze: {
        light: '#F6C29A',
        mid: '#C0723A',
        rim: '#8F4A1C',
        ribbon: '#B45309',
        glyph: '#4A220A',
    },
};
