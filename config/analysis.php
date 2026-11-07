<?php

declare(strict_types=1);

return [
    'openai' => [
        'model' => env('ANALYSIS_OPENAI_MODEL', 'gpt-5.1'),
        'temperature' => (float) env('ANALYSIS_OPENAI_TEMPERATURE', 0.2),
        'timeout' => (int) env('ANALYSIS_OPENAI_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quality Filters
    |--------------------------------------------------------------------------
    |
    | How confident the model must be before something is shown. Factual and
    | definitional gaps at or above "finding" are shown as findings; between
    | "question" and "finding" they are asked as open questions instead, and
    | below that they are discarded. Contested value assumptions are always
    | asked as value questions. Each type keeps at most "max_findings" items.
    |
    */

    'confidence' => [
        'finding' => (float) env('ANALYSIS_FINDING_CONFIDENCE', 0.8),
        'question' => (float) env('ANALYSIS_QUESTION_CONFIDENCE', 0.5),
        'technique' => (float) env('ANALYSIS_TECHNIQUE_CONFIDENCE', 0.6),
    ],

    'max_findings' => [
        'fallacy' => 6,
        'rhetoric' => 6,
        'questions' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Argument Map Prompt
    |--------------------------------------------------------------------------
    |
    | One prompt reconstructs the argument (Toulmin: claim, grounds, warrant;
    | Walton: critical questions), evaluates its unstated assumptions and
    | lists persuasion techniques. {FALLACIES} and {TECHNIQUES} are replaced
    | with the catalogs below. Free text is written in Armenian.
    |
    */

    'prompt' => <<<'EOT'
You are an expert in argumentation theory analyzing Armenian-language public writing: political statements, news, opinion pieces and social media posts. Your results are shown to the public and may be contested by the author, so be fair to every side: apply exactly the same standards whatever the political position, and reconstruct arguments charitably before evaluating them.

STEP 1 - TEXT KIND
Decide what the text is:
- "argument": it argues for a position on a matter of public interest (politics, society, institutions, public figures).
- "informational": it reports or announces without arguing.
- "personal": it shares personal experiences, tastes or plans.
Only "argument" texts are analyzed further. For the other kinds, return empty "claims" and "techniques" lists.

STEP 2 - ARGUMENT MAP
Identify who is arguing (the author, or a quoted speaker in a news report) and summarize what they argue in one or two sentences. Then reconstruct at most 3 main claims. For each claim:
- list the reasons the text gives, marking each as factual, a value judgment or an interpretation, and whether the text supports it or merely asserts it;
- make explicit the unstated assumptions (warrants) needed for the reasons to support the claim. This is the most important step.

STEP 3 - EVALUATE EACH ASSUMPTION
- "acceptance": "widely_shared" for premises most reasonable people accept (for example "the state has a duty to honor those who died defending the country"). These are legitimate starting points, never flaws: set "gap" and "question" to null.
- "contested" or "doubtful" assumptions may contain a gap. Describe the specific step that does not follow in "gap.issue", grounded in the wording. Name a fallacy only if one clearly and unambiguously fits; otherwise use null. Never call a widely shared value a fallacy, and when a contested assumption stretches a shared value into something stronger, say exactly which step is the stretch.
- "confidence" is your probability, from 0 to 1, that a careful, impartial expert would agree the gap is real.
- Provide an open "question" a reader could investigate:
  - always for "contested" value assumptions. These are disagreements about values that evidence cannot settle, so they are asked, never asserted: set the question "kind" to "value";
  - for factual or definitional gaps when you are not certain (confidence below 0.8), or when the text asserts an important fact without support: set the question "kind" to "factual" if documents, data or records could settle it.
  Otherwise set "question" to null.
- For a "value" question, draft a public "poll": a generalized, neutrally worded question that names no people, parties, outlets or events, so that it can be answered without knowing the text, with 3 or 4 balanced answer options. Add a "mirror": the same question with the political direction reversed (for example, the same reasoning applied to the opposite camp), or null if no meaningful mirror exists.

STEP 4 - PERSUASION TECHNIQUES
List at most 5 techniques that do real persuasive work, each with a verbatim quote. Ordinary emphasis or vivid wording is not a technique. Use only: {TECHNIQUES}.

SCALES
- strength (1 to 10): how much the conclusion depends on the flawed step, or the intensity of a technique. 1-3 incidental, 4-6 clear, 7-8 central, 9-10 the whole argument rests on it. Most items should be 4 to 7.
- ethics: "neutral" for an honest mistake or legitimate persuasion, "questionable" when it pushes the audience toward an unsupported conclusion, "unethical" only for clear deception or for dehumanizing people or inciting hostility against them.

RULES
- Every "quote" is an exact, contiguous excerpt from the text of 3 to 25 words, without added quotation marks or ellipses.
- Write all free text in clear, natural Armenian, one or two sentences each. Never use English or other Latin-script words, and do not moralize.
- Fallacy names: {FALLACIES}.
EOT,

    'evidence_prompt' => <<<'EOT'
You check evidence for a public fact-checking discussion about an Armenian-language text. You receive a claim from the text, an open question about it, and an excerpt from a source a reader cited. Decide strictly whether the excerpt itself provides evidence that supports the claim, refutes it, or is irrelevant to it. Evidence must be in the excerpt; do not use outside knowledge, and treat opinions in the excerpt as weaker than facts. Explain your decision in one or two sentences of clear Armenian, without English words.
EOT,

    /*
    |--------------------------------------------------------------------------
    | Catalogs
    |--------------------------------------------------------------------------
    |
    | The only names the model may use, with the Armenian name shown to users
    | and the definition given to the model. Keeping a closed list makes the
    | results consistent and comparable between analyses.
    |
    */

    'catalog' => [
        'fallacy' => [
            'Ad hominem' => ['armenian' => 'Անձին ուղղված փաստարկ', 'definition' => 'rejecting a claim by attacking the person who makes it instead of the claim'],
            'Straw man' => ['armenian' => 'Խրտվիլակի փաստարկ', 'definition' => 'refuting a distorted or exaggerated version of an opposing position'],
            'False dilemma' => ['armenian' => 'Կեղծ երկընտրանք', 'definition' => 'presenting two options as the only possibilities when others exist'],
            'Slippery slope' => ['armenian' => 'Սայթաքուն լանջ', 'definition' => 'claiming one step will inevitably lead to extreme consequences without showing why'],
            'Hasty generalization' => ['armenian' => 'Շտապողական ընդհանրացում', 'definition' => 'drawing a broad conclusion from too few or unrepresentative cases'],
            'Cherry picking' => ['armenian' => 'Փաստերի ընտրողական օգտագործում', 'definition' => 'citing only the evidence that supports a conclusion while ignoring evidence against it'],
            'Appeal to authority' => ['armenian' => 'Հեղինակության կոչ', 'definition' => 'treating a claim as true because an authority, often outside their expertise, said so'],
            'Appeal to popularity' => ['armenian' => 'Մեծամասնության կոչ', 'definition' => 'arguing a claim is true or right because many people believe or do it'],
            'Appeal to tradition' => ['armenian' => 'Ավանդույթի կոչ', 'definition' => 'arguing something is right because it has always been done that way'],
            'Appeal to nature' => ['armenian' => 'Բնականության կոչ', 'definition' => 'arguing something is good because it is natural, or bad because it is not'],
            'Appeal to ignorance' => ['armenian' => 'Անգիտության կոչ', 'definition' => 'concluding a claim is true because it has not been proven false, or vice versa'],
            'Circular reasoning' => ['armenian' => 'Շրջանաձև դատողություն', 'definition' => 'using the conclusion, in other words, as one of its own premises'],
            'False cause' => ['armenian' => 'Կեղծ պատճառականություն', 'definition' => 'assuming that because one thing followed or accompanied another, it caused it'],
            'Red herring' => ['armenian' => 'Ուշադրության շեղում', 'definition' => 'introducing an irrelevant point to divert attention from the issue'],
            'Whataboutism' => ['armenian' => '«Իսկ նրա՞նք» փաստարկ', 'definition' => 'deflecting a criticism by pointing to a different wrongdoing'],
            'Tu quoque' => ['armenian' => '«Դու էլ» փաստարկ', 'definition' => 'rejecting criticism because the critic is guilty of the same thing'],
            'Loaded question' => ['armenian' => 'Կանխադրույթով հարց', 'definition' => 'a question that presupposes an unproven or disputed claim'],
            'Moving the goalposts' => ['armenian' => 'Չափանիշների տեղաշարժ', 'definition' => 'changing the standard of proof once the original one has been met'],
            'No true Scotsman' => ['armenian' => '«Իսկական շոտլանդացի» սխալ', 'definition' => 'redefining a group to exclude counterexamples to a generalization'],
            'Genetic fallacy' => ['armenian' => 'Ծագման սխալ', 'definition' => 'judging a claim by its origin or source rather than its merits'],
            'Equivocation' => ['armenian' => 'Երկիմաստություն', 'definition' => 'shifting between different meanings of a word within the same argument'],
            'Guilt by association' => ['armenian' => 'Մեղքը կապի միջոցով', 'definition' => 'discrediting a person or idea because of who else is associated with it'],
            'Shifting the burden of proof' => ['armenian' => 'Ապացուցման բեռի տեղափոխում', 'definition' => 'demanding that others disprove a claim instead of supporting it'],
            'Composition or division' => ['armenian' => 'Մասի և ամբողջի սխալ', 'definition' => 'assuming what is true of the parts is true of the whole, or vice versa'],
        ],
        'rhetoric' => [
            'Appeal to emotion' => ['armenian' => 'Զգացմունքների կոչ', 'definition' => 'evoking feelings such as pity, anger or pride in place of reasons'],
            'Appeal to fear' => ['armenian' => 'Վախի կոչ', 'definition' => 'persuading by making the audience afraid of the alternative'],
            'Appeal to patriotism' => ['armenian' => 'Հայրենասիրական կոչ', 'definition' => 'framing agreement as a duty of loyalty to the nation or homeland'],
            'Appeal to history or legacy' => ['armenian' => 'Պատմության և ժառանգության կոչ', 'definition' => 'invoking ancestors, history or how future generations will judge'],
            'Loaded language' => ['armenian' => 'Երանգավորված բառապաշար', 'definition' => 'words with strong connotations chosen to sway judgement rather than describe'],
            'Name-calling' => ['armenian' => 'Պիտակավորում', 'definition' => 'attaching a negative label to a person or group instead of addressing what they do or say'],
            'Demonization' => ['armenian' => 'Դիվայնացում', 'definition' => 'portraying opponents as evil, enemies or traitors'],
            'Us vs. them' => ['armenian' => '«Մենք և նրանք» հակադրություն', 'definition' => 'framing an issue as a conflict between an in-group and an out-group'],
            'Hyperbole' => ['armenian' => 'Չափազանցություն', 'definition' => 'deliberate exaggeration for effect'],
            'Rhetorical question' => ['armenian' => 'Հռետորական հարց', 'definition' => 'a question asked for effect that implies its own answer'],
            'Repetition' => ['armenian' => 'Կրկնություն', 'definition' => 'repeating a word, phrase or idea to drive it home'],
            'Metaphor' => ['armenian' => 'Փոխաբերություն', 'definition' => 'describing one thing as another to transfer its associations'],
            'Irony or sarcasm' => ['armenian' => 'Հեգնանք կամ սարկազմ', 'definition' => 'saying the opposite of what is meant, often to mock'],
            'Euphemism' => ['armenian' => 'Մեղմասություն', 'definition' => 'a mild expression used to make something unpleasant sound acceptable'],
            'Glittering generalities' => ['armenian' => 'Փայլուն ընդհանրություններ', 'definition' => 'vague, emotionally appealing words such as freedom or dignity used without content'],
            'Scapegoating' => ['armenian' => 'Քավության նոխազի որոնում', 'definition' => 'blaming a person or group for complex problems'],
            'Vague attribution' => ['armenian' => 'Անորոշ հղում', 'definition' => 'attributing a claim to unnamed people, such as "everyone" or "the whole society"'],
            'Bandwagon framing' => ['armenian' => 'Մեծամասնության ճնշում', 'definition' => 'presenting a view as what everyone already thinks, so the reader should too'],
        ],
    ],

    'sample_text' => 'Մեր քաղաքական գործիչները մշտապես խոստանում են, որ ամեն ինչ կփոխվի, բայց իրականում ոչինչ չի փոխվում։ Եթե դու համաձայն չես կառավարության հետ, ուրեմն դու հայրենիքի թշնամի ես։ Բոլորը գիտեն, որ ընդդիմությունը վճարվում է արտասահմանից, ուստի նրանց ասածներին պետք չէ լրջորեն վերաբերվել։ Մենք կամ պետք է ընդունենք այս բարեփոխումները հիմա, կամ էլ մեր երկիրը կկործանվի։ Մեր նախնիները երբեք թույլ չէին տա, որ նման բան տեղի ունենա, ուստի մենք էլ չպետք է թույլ տանք։ Ով դեմ է այս ծրագրին, նա պարզապես չի սիրում իր ժողովրդին և չի ցանկանում տեսնել մեր զարգացումը։',

    'min_words' => 30,

    'max_words' => 500,

    'min_armenian_ratio' => 0.8,
];
