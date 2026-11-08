export type SubmissionStateValue =
    | 'pending'
    | 'processing'
    | 'analyzed'
    | 'failed';

export type AnalysisTypeValue = 'fallacy' | 'rhetoric' | 'questions';

export type AnalysisStatusValue =
    | 'not_started'
    | 'running'
    | 'completed'
    | 'failed';

export type EthicsValue = 'neutral' | 'questionable' | 'unethical';

export type UserSummary = {
    id: string;
    name: string;
    reputation: number;
};

export type VotableType = 'user_note' | 'comment' | 'evidence';

export type VoteValue = 1 | -1;

export type SubmissionKindValue = 'argument' | 'informational' | 'personal';

export type QuestionKindValue = 'factual' | 'value';

export type QuestionStatusValue = 'open' | 'resolved';

export type VerdictValue = 'supported' | 'refuted' | 'contested';

export type StanceValue = 'supports' | 'refutes';

export type EvidenceVerificationValue =
    | 'pending'
    | 'verified'
    | 'not_found'
    | 'unreachable';

export type EvidenceAssessmentValue = 'supports' | 'refutes' | 'irrelevant';

export type PollStatusValue = 'draft' | 'published' | 'closed';

export type Evidence = {
    id: number;
    stance: StanceValue;
    url: string;
    source_host: string;
    page_title: string | null;
    quote: string;
    explanation: string | null;
    verification: EvidenceVerificationValue;
    assessment: EvidenceAssessmentValue | null;
    assessment_reason: string | null;
    author: UserSummary;
    score: number;
    my_vote: VoteValue | null;
    created_at_human: string;
};

export type PollResult = {
    id: number;
    question: string;
    status: PollStatusValue;
    total_votes: number;
    options: { id: number; label: string; votes: number; share: number }[];
};

export type PublicPoll = {
    id: number;
    question: string;
    status: PollStatusValue;
    is_open: boolean;
    options: { id: number; label: string }[];
    my_option_id: number | null;
    my_reason: string | null;
    results: PollResult | null;
    source: { submission_id: number; title: string | null } | null;
    published_at_human: string | null;
};

export type ArgumentAssumption = {
    assumption: string;
    kind: 'value' | 'factual' | 'definitional';
    acceptance: 'widely_shared' | 'contested' | 'doubtful';
    assessment: string;
    confidence: number;
};

export type ArgumentClaim = {
    claim: string;
    quote: string;
    reasons: {
        reason: string;
        kind: 'factual' | 'value' | 'interpretation';
        support: 'supported' | 'asserted';
    }[];
    assumptions: ArgumentAssumption[];
};

export type ArgumentMap = {
    text_kind: SubmissionKindValue;
    speaker: string;
    summary: string;
    claims: ArgumentClaim[];
    overall: { reasoning: 'strong' | 'mixed' | 'weak'; comment: string };
};

export type Submission = {
    id: number;
    title: string;
    excerpt: string;
    source: string | null;
    source_host: string | null;
    state: SubmissionStateValue;
    state_label: string;
    kind: SubmissionKindValue | null;
    kind_label: string | null;
    is_in_progress: boolean;
    counts: Record<AnalysisTypeValue, number>;
    word_count: number;
    author: UserSummary;
    is_owner: boolean;
    created_at: string;
    created_at_human: string;
};

export type Comment = {
    id: number;
    text: string;
    author: UserSummary;
    score: number;
    my_vote: VoteValue | null;
    is_owner: boolean;
    created_at_human: string;
};

export type AnalysisNote = {
    id: number;
    annotation_id: string | null;
    label: string | null;
    aspect_label: string | null;
    text: string;
    ethics: EthicsValue;
    strength: number;
    comments: Comment[];
    question_kind: QuestionKindValue | null;
    status: QuestionStatusValue | null;
    verdict: VerdictValue | null;
    verdict_label: string | null;
    resolution: string | null;
    resolved_at_human: string | null;
    evidence?: Evidence[];
};

export type SubmissionAnalysis = {
    id: number;
    type: AnalysisTypeValue;
    label: string;
    status: AnalysisStatusValue;
    notes: AnalysisNote[];
};

export type UserNote = {
    id: number;
    annotation_id: string;
    label: string | null;
    text: string;
    author: UserSummary;
    score: number;
    my_vote: VoteValue | null;
    is_owner: boolean;
    created_at_human: string;
    comments: Comment[];
};

export type SubmissionProgress = {
    source: 'text' | 'url';
    url: string | null;
    stage: 'queued' | 'fetching' | 'analyzing' | 'done' | 'failed';
    is_in_progress: boolean;
    submission_id: number | null;
    title: string | null;
    error: string | null;
};

export type Invitation = {
    id: number;
    email: string | null;
    code: string;
    is_used: boolean;
    invitee: UserSummary | null;
    used_at_human: string | null;
    created_at_human: string;
};

export type DuplicateMatch = {
    id: number;
    title: string | null;
    source: string | null;
    preview: string;
    similarity: number;
    match: string;
    match_label: string;
    created_at_human: string;
};

export type ReportableType =
    | 'submission'
    | 'analysis_note'
    | 'user_note'
    | 'comment';

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
};

export type UsageMeter = {
    limit: number;
    used: number;
    remaining: number;
};

export type Usage = {
    plan: 'standard' | 'pro';
    plan_label: string;
    submissions: UsageMeter;
    invitations: UsageMeter;
};

export type SubmissionSource = {
    id: number;
    title: string;
    source_host: string | null;
};

export type QuestionSummary = {
    id: number;
    question: string;
    passage: string | null;
    status: QuestionStatusValue;
    verdict: VerdictValue | null;
    verdict_label: string | null;
    evidence_count: number;
    comments_count: number;
    source: SubmissionSource;
    created_at_human: string;
    resolved_at_human: string | null;
};

export type NotificationKindValue =
    | 'analysis_completed'
    | 'analysis_failed'
    | 'comment_posted'
    | 'community_note_added'
    | 'evidence_checked'
    | 'question_resolved'
    | 'invitee_joined'
    | 'badge_earned'
    | 'privilege_unlocked';

export type AppNotification = {
    id: string;
    kind: NotificationKindValue | null;
    headline: string;
    excerpt: string | null;
    actors: string[];
    is_read: boolean;
    created_at_human: string | null;
};

export type NotificationKindOption = {
    value: NotificationKindValue;
    label: string;
    description: string;
};
