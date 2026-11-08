import type { LucideIcon } from '@lucide/vue';
import {
    Award,
    Bell,
    CircleAlert,
    CircleCheck,
    FileSearch,
    Gavel,
    MessageCircle,
    MessagesSquare,
    Unlock,
    UserPlus,
} from '@lucide/vue';
import type { NotificationKindValue } from '@/types';

export type NotificationKindMeta = {
    icon: LucideIcon;
    class: string;
};

export const notificationKinds: Record<
    NotificationKindValue,
    NotificationKindMeta
> = {
    analysis_completed: {
        icon: CircleCheck,
        class: 'bg-brand-soft text-brand-strong',
    },
    analysis_failed: {
        icon: CircleAlert,
        class: 'bg-destructive/10 text-destructive',
    },
    comment_posted: {
        icon: MessageCircle,
        class: 'bg-community-soft text-community',
    },
    community_note_added: {
        icon: MessagesSquare,
        class: 'bg-community-soft text-community',
    },
    evidence_checked: {
        icon: FileSearch,
        class: 'bg-questions-soft text-questions',
    },
    question_resolved: {
        icon: Gavel,
        class: 'bg-questions-soft text-questions',
    },
    invitee_joined: {
        icon: UserPlus,
        class: 'bg-rhetoric-soft text-rhetoric',
    },
    badge_earned: {
        icon: Award,
        class: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    },
    privilege_unlocked: {
        icon: Unlock,
        class: 'bg-brand-soft text-brand-strong',
    },
};

export const fallbackNotificationKind: NotificationKindMeta = {
    icon: Bell,
    class: 'bg-surface text-muted-foreground',
};
