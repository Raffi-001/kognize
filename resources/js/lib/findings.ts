import type { AspectKey } from '@/lib/analysis';
import type {
    AnalysisNote,
    Comment,
    EthicsValue,
    Evidence,
    QuestionKindValue,
    QuestionStatusValue,
    SubmissionAnalysis,
    UserNote,
    VerdictValue,
} from '@/types';

/** Category order also decides which underline wins where findings overlap. */
export const findingOrder: AspectKey[] = [
    'fallacy',
    'rhetoric',
    'questions',
    'community',
];

export type FindingQuestion = {
    kind: QuestionKindValue;
    status: QuestionStatusValue;
    verdict: VerdictValue | null;
    verdictLabel: string | null;
    resolution: string | null;
    resolvedAtHuman: string | null;
    evidence: Evidence[];
};

/** The question state of a note, or null when the note is not a question. */
export function questionOf(note: AnalysisNote): FindingQuestion | null {
    return note.question_kind
        ? {
              kind: note.question_kind,
              status: note.status ?? 'open',
              verdict: note.verdict,
              verdictLabel: note.verdict_label,
              resolution: note.resolution,
              resolvedAtHuman: note.resolved_at_human,
              evidence: note.evidence ?? [],
          }
        : null;
}

export type Finding = {
    key: string;
    type: AspectKey;
    id: number;
    title: string;
    subtitle: string | null;
    label: string | null;
    text: string;
    ethics: EthicsValue | null;
    strength: number | null;
    comments: Comment[];
    author: UserNote['author'] | null;
    isOwner: boolean;
    score: number;
    myVote: UserNote['my_vote'];
    createdAtHuman: string | null;
    isMinor: boolean;
    question: FindingQuestion | null;
    /** Character offset of the quoted passage in the original text. */
    start: number | null;
    end: number | null;
};

export type Segment = {
    text: string;
    keys: string[];
    types: AspectKey[];
};

export type Paragraph = Segment[];

const armenian = /[԰-֏]/u;

/**
 * Splits labels such as "Կեղծ երկընտրանք (False Dilemma)" into an English
 * title with the Armenian name as subtitle.
 */
export function splitAspectLabel(label: string): {
    title: string;
    subtitle: string | null;
} {
    const match = /^(.*?)\s*\(([^)]+)\)\s*$/u.exec(label.trim());

    if (!match) {
        return { title: label.trim(), subtitle: null };
    }

    const [, outside, inside] = match;

    return armenian.test(outside) && !armenian.test(inside)
        ? { title: inside.trim(), subtitle: outside.trim() }
        : { title: outside.trim(), subtitle: inside.trim() };
}

function escapeRegExp(value: string): string {
    return value.replaceAll(/[.*+?^${}()|[\]\\]/g, String.raw`\$&`);
}

/** Finds a quoted passage in the text, tolerating whitespace and edge punctuation differences. */
export function locate(
    body: string,
    label: string | null,
): [number, number] | null {
    const needle = label
        ?.trim()
        .replaceAll(/^[«"„“'\s]+|[»"”'.,։:;!?\s]+$/gu, '');

    if (!needle) {
        return null;
    }

    const exact = body.indexOf(needle);

    if (exact !== -1) {
        return [exact, exact + needle.length];
    }

    const pattern = new RegExp(
        needle
            .split(/\s+/u)
            .map(escapeRegExp)
            .join(String.raw`\s+`),
        'u',
    );
    const match = pattern.exec(body);

    return match ? [match.index, match.index + match[0].length] : null;
}

function isMinor(note: AnalysisNote, type: AspectKey): boolean {
    return (
        type !== 'questions' && note.strength <= 4 && note.ethics === 'neutral'
    );
}

export function buildFindings(
    body: string,
    analyses: SubmissionAnalysis[],
    userNotes: UserNote[],
): Finding[] {
    const findings: Finding[] = [];

    for (const analysis of analyses) {
        for (const note of analysis.notes) {
            const position = locate(body, note.label);
            const { title, subtitle } =
                analysis.type === 'questions'
                    ? { title: note.aspect_label ?? note.text, subtitle: null }
                    : splitAspectLabel(note.aspect_label ?? 'Finding');

            findings.push({
                key: `${analysis.type}-${note.id}`,
                type: analysis.type,
                id: note.id,
                title,
                subtitle,
                label: note.label,
                text: note.text,
                ethics: note.ethics,
                strength: note.strength,
                comments: note.comments,
                author: null,
                isOwner: false,
                score: 0,
                myVote: null,
                createdAtHuman: null,
                isMinor: isMinor(note, analysis.type),
                question: questionOf(note),
                start: position?.[0] ?? null,
                end: position?.[1] ?? null,
            });
        }
    }

    for (const note of userNotes) {
        const position = locate(body, note.label);

        findings.push({
            key: `community-${note.id}`,
            type: 'community',
            id: note.id,
            title: note.author.name,
            subtitle: note.created_at_human,
            label: note.label,
            text: note.text,
            ethics: null,
            strength: null,
            comments: note.comments,
            author: note.author,
            isOwner: note.is_owner,
            score: note.score,
            myVote: note.my_vote,
            createdAtHuman: note.created_at_human,
            isMinor: false,
            question: null,
            start: position?.[0] ?? null,
            end: position?.[1] ?? null,
        });
    }

    return findings.toSorted(
        (a, b) =>
            (a.start ?? Number.MAX_SAFE_INTEGER) -
                (b.start ?? Number.MAX_SAFE_INTEGER) ||
            findingOrder.indexOf(a.type) - findingOrder.indexOf(b.type),
    );
}

/**
 * Cuts the text at every finding boundary so overlapping findings can be
 * rendered as plain segments, then groups the segments into paragraphs.
 */
export function buildParagraphs(
    body: string,
    findings: Finding[],
): Paragraph[] {
    const anchored = findings.filter(
        (finding): finding is Finding & { start: number; end: number } =>
            finding.start !== null && finding.end !== null,
    );

    const boundaries = [
        ...new Set([
            0,
            body.length,
            ...anchored.flatMap((finding) => [finding.start, finding.end]),
        ]),
    ].toSorted((a, b) => a - b);

    const paragraphs: Paragraph[] = [[]];

    for (let index = 0; index < boundaries.length - 1; index++) {
        const from = boundaries[index];
        const to = boundaries[index + 1];
        const covering = anchored
            .filter((finding) => finding.start <= from && finding.end >= to)
            .toSorted(
                (a, b) =>
                    findingOrder.indexOf(a.type) - findingOrder.indexOf(b.type),
            );

        const pieces = body.slice(from, to).split(/\n{2,}/u);

        pieces.forEach((piece, position) => {
            if (position > 0) {
                paragraphs.push([]);
            }

            if (piece !== '') {
                paragraphs.at(-1)?.push({
                    text: piece,
                    keys: covering.map((finding) => finding.key),
                    types: covering.map((finding) => finding.type),
                });
            }
        });
    }

    return paragraphs.filter((paragraph) => paragraph.length > 0);
}

const ethicsWeight: Record<EthicsValue, number> = {
    neutral: 0.35,
    questionable: 0.7,
    unethical: 1,
};

const typeWeight: Partial<Record<AspectKey, number>> = {
    fallacy: 1,
    rhetoric: 0.55,
};

export type Health = {
    score: number;
    verdict: string;
    tone: 'good' | 'fair' | 'poor';
    severe: number;
};

/** A 0–100 indicator of argument quality derived from finding strength and ethics. */
export function computeHealth(findings: Finding[]): Health {
    let penalty = 0;
    let severe = 0;

    for (const finding of findings) {
        const weight = typeWeight[finding.type];

        if (!weight || finding.strength === null || finding.ethics === null) {
            continue;
        }

        penalty +=
            (finding.strength / 10) *
            ethicsWeight[finding.ethics] *
            weight *
            14;
        severe +=
            finding.strength >= 8 || finding.ethics === 'unethical' ? 1 : 0;
    }

    const score = Math.max(0, Math.min(100, Math.round(100 - penalty)));

    const verdict =
        score >= 90
            ? 'Sound and measured'
            : score >= 75
              ? 'Mostly sound'
              : score >= 55
                ? 'Persuasive, with weak spots'
                : score >= 35
                  ? 'Heavily slanted'
                  : 'Manipulative';

    return {
        score,
        verdict,
        tone: score >= 75 ? 'good' : score >= 50 ? 'fair' : 'poor',
        severe,
    };
}

export type GlossaryEntry = {
    name: string;
    armenian: string;
    definition: string;
};

const glossary: Record<string, GlossaryEntry> = {
    'false dilemma': {
        name: 'False dilemma',
        armenian: 'Կեղծ երկընտրանք',
        definition:
            'Presenting a limited set of options as the only ones, when others exist.',
    },
    'ad hominem': {
        name: 'Ad hominem',
        armenian: 'Անձին ուղղված փաստարկ',
        definition:
            'Attacking the person making an argument instead of the argument itself.',
    },
    'straw man': {
        name: 'Straw man',
        armenian: 'Խրտվիլակի փաստարկ',
        definition: 'Misrepresenting a position to make it easier to attack.',
    },
    'slippery slope': {
        name: 'Slippery slope',
        armenian: 'Սայթաքուն լանջ',
        definition:
            'Claiming one step will inevitably lead to extreme consequences without showing how.',
    },
    'appeal to authority': {
        name: 'Appeal to authority',
        armenian: 'Հեղինակության կոչ',
        definition:
            'Treating a claim as true because an authority said it, rather than because of evidence.',
    },
    'appeal to emotion': {
        name: 'Appeal to emotion',
        armenian: 'Զգացմունքների կոչ',
        definition:
            'Using feelings such as fear, pity or pride in place of reasons.',
    },
    'appeal to fear': {
        name: 'Appeal to fear',
        armenian: 'Վախի կոչ',
        definition:
            'Persuading by making the audience afraid of the alternative.',
    },
    'appeal to tradition': {
        name: 'Appeal to tradition',
        armenian: 'Ավանդույթի կոչ',
        definition:
            'Arguing something is right because it has always been done that way.',
    },
    'hasty generalization': {
        name: 'Hasty generalization',
        armenian: 'Շտապողական ընդհանրացում',
        definition:
            'Drawing a broad conclusion from too few or unrepresentative cases.',
    },
    bandwagon: {
        name: 'Bandwagon',
        armenian: 'Մեծամասնության կոչ',
        definition:
            'Arguing something is true or good because many people believe it.',
    },
    'red herring': {
        name: 'Red herring',
        armenian: 'Ուշադրության շեղում',
        definition:
            'Introducing an irrelevant point to divert attention from the issue.',
    },
    'circular reasoning': {
        name: 'Circular reasoning',
        armenian: 'Շրջանաձև դատողություն',
        definition: 'Using the conclusion as one of the premises.',
    },
    'false cause': {
        name: 'False cause',
        armenian: 'Կեղծ պատճառականություն',
        definition:
            'Assuming that because one thing followed another, it was caused by it.',
    },
    'tu quoque': {
        name: 'Tu quoque',
        armenian: '«Դու էլ» փաստարկ',
        definition:
            'Deflecting criticism by pointing out that the critic does the same.',
    },
    'loaded question': {
        name: 'Loaded question',
        armenian: 'Կանխադրույթով հարց',
        definition: 'Asking a question that presupposes an unproven claim.',
    },
    hyperbole: {
        name: 'Hyperbole',
        armenian: 'Չափազանցություն',
        definition: 'Deliberate exaggeration for emphasis or effect.',
    },
    metaphor: {
        name: 'Metaphor',
        armenian: 'Փոխաբերություն',
        definition:
            'Describing one thing as another to transfer its associations.',
    },
    'rhetorical question': {
        name: 'Rhetorical question',
        armenian: 'Հռետորական հարց',
        definition: 'A question asked for effect, implying its own answer.',
    },
    'us vs them': {
        name: 'Us vs. them',
        armenian: '«Մենք և նրանք» հակադրություն',
        definition:
            'Framing an issue as a conflict between an in-group and an out-group.',
    },
    'name calling': {
        name: 'Name-calling',
        armenian: 'Պիտակավորում',
        definition:
            'Attaching a negative label to a person or group instead of addressing what they do or say.',
    },
    demonization: {
        name: 'Demonization',
        armenian: 'Դիվայնացում',
        definition: 'Portraying opponents as evil, enemies or traitors.',
    },
    'loaded language': {
        name: 'Loaded language',
        armenian: 'Երանգավորված բառապաշար',
        definition:
            'Words with strong connotations chosen to sway judgement rather than describe.',
    },
    whataboutism: {
        name: 'Whataboutism',
        armenian: '«Իսկ նրա՞նք» փաստարկ',
        definition:
            'Deflecting a criticism by pointing to a different wrongdoing.',
    },
    'cherry picking': {
        name: 'Cherry picking',
        armenian: 'Փաստերի ընտրողական օգտագործում',
        definition:
            'Citing only the evidence that supports a conclusion while ignoring evidence against it.',
    },
    'vague attribution': {
        name: 'Vague attribution',
        armenian: 'Անորոշ հղում',
        definition:
            'Attributing a claim to unnamed people, such as "everyone" or "the whole society".',
    },
    'appeal to patriotism': {
        name: 'Appeal to patriotism',
        armenian: 'Հայրենասիրական կոչ',
        definition:
            'Framing agreement as a duty of loyalty to the nation or homeland.',
    },
    scapegoating: {
        name: 'Scapegoating',
        armenian: 'Քավության նոխազի որոնում',
        definition: 'Blaming a person or group for complex problems.',
    },
    'glittering generalities': {
        name: 'Glittering generalities',
        armenian: 'Փայլուն ընդհանրություններ',
        definition:
            'Vague, emotionally appealing words such as freedom or dignity used without content.',
    },
    'genetic fallacy': {
        name: 'Genetic fallacy',
        armenian: 'Ծագման սխալ',
        definition:
            'Judging a claim by its origin or source rather than its merits.',
    },
    generalization: {
        name: 'Generalization',
        armenian: 'Ընդհանրացում',
        definition:
            'Attributing a trait to a whole group based on some of its members.',
    },
};

const aliases: Record<string, string> = {
    'false dichotomy': 'false dilemma',
    'black and white': 'false dilemma',
    strawman: 'straw man',
    'appeal to popularity': 'bandwagon',
    'ad populum': 'bandwagon',
    'post hoc': 'false cause',
    'begging the question': 'circular reasoning',
    pathos: 'appeal to emotion',
    'us versus them': 'us vs them',
};

export function lookupGlossary(title: string): GlossaryEntry | null {
    const normalized = title
        .toLowerCase()
        .replaceAll(/[^a-z\s]/g, ' ')
        .replaceAll(/\s+/g, ' ')
        .trim();

    const direct = Object.keys(glossary).find((key) =>
        normalized.includes(key),
    );

    if (direct) {
        return glossary[direct];
    }

    const alias = Object.keys(aliases).find((key) => normalized.includes(key));

    return alias ? glossary[aliases[alias]] : null;
}
