<script setup lang="ts">
import { ChevronDown, Network } from '@lucide/vue';
import { ref } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import type { ArgumentAssumption, ArgumentMap } from '@/types';

defineProps<{
    map: ArgumentMap;
}>();

const open = ref(false);

const acceptance: Record<
    ArgumentAssumption['acceptance'],
    { label: string; class: string }
> = {
    widely_shared: {
        label: 'Widely shared',
        class: 'bg-brand-soft text-brand-strong',
    },
    contested: {
        label: 'Contested',
        class: 'bg-questions-soft text-questions',
    },
    doubtful: {
        label: 'Doubtful',
        class: 'bg-destructive/10 text-destructive',
    },
};

const reasonKind = {
    factual: 'Fact',
    value: 'Value',
    interpretation: 'Interpretation',
} as const;

const reasoning = {
    strong: 'Strong reasoning',
    mixed: 'Mixed reasoning',
    weak: 'Weak reasoning',
} as const;
</script>

<template>
    <Collapsible v-model:open="open" class="rounded-2xl bg-surface">
        <CollapsibleTrigger
            class="flex w-full items-start gap-3 rounded-2xl p-4 text-left transition-colors hover:bg-surface-strong/60 sm:p-5"
        >
            <span
                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-background text-brand-strong shadow-xs"
            >
                <Network class="size-4" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="flex flex-wrap items-center gap-x-2 text-sm">
                    <span class="font-medium">How the argument is built</span>
                    <span class="text-xs text-muted-foreground"
                        >{{ map.speaker }} · {{ map.claims.length }}
                        {{ map.claims.length === 1 ? 'claim' : 'claims' }} ·
                        {{ reasoning[map.overall.reasoning] }}</span
                    >
                </span>
                <span
                    lang="hy"
                    class="mt-1 block text-sm leading-relaxed text-foreground/80"
                    :class="{ 'line-clamp-2': !open }"
                    >{{ map.summary }}</span
                >
            </span>
            <ChevronDown
                class="mt-1 size-4 shrink-0 text-muted-foreground transition-transform"
                :class="{ 'rotate-180': open }"
            />
        </CollapsibleTrigger>

        <CollapsibleContent>
            <div class="grid gap-3 px-4 pb-4 sm:px-5 sm:pb-5">
                <section
                    v-for="(claim, index) in map.claims"
                    :key="index"
                    class="rounded-xl bg-background p-4"
                >
                    <p
                        class="text-[10.5px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Claim {{ index + 1 }}
                    </p>
                    <p lang="hy" class="mt-1 text-sm font-medium">
                        {{ claim.claim }}
                    </p>

                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <div>
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Reasons given
                            </p>
                            <ul class="mt-1.5 grid gap-1.5">
                                <li
                                    v-for="(reason, position) in claim.reasons"
                                    :key="position"
                                    class="text-[0.8rem] leading-relaxed"
                                >
                                    <span
                                        class="mr-1.5 rounded bg-surface px-1.5 py-px text-[10px] font-medium text-muted-foreground"
                                        >{{ reasonKind[reason.kind]
                                        }}{{
                                            reason.support === 'asserted'
                                                ? ' · asserted'
                                                : ''
                                        }}</span
                                    >
                                    <span lang="hy">{{ reason.reason }}</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Unstated assumptions
                            </p>
                            <ul class="mt-1.5 grid gap-2">
                                <li
                                    v-for="(
                                        assumption, position
                                    ) in claim.assumptions"
                                    :key="position"
                                    class="text-[0.8rem] leading-relaxed"
                                >
                                    <span
                                        :class="
                                            cn(
                                                'mr-1.5 rounded px-1.5 py-px text-[10px] font-medium',
                                                acceptance[
                                                    assumption.acceptance
                                                ].class,
                                            )
                                        "
                                        >{{
                                            acceptance[assumption.acceptance]
                                                .label
                                        }}</span
                                    >
                                    <span lang="hy">{{
                                        assumption.assumption
                                    }}</span>
                                    <span
                                        lang="hy"
                                        class="mt-0.5 block text-xs text-muted-foreground"
                                        >{{ assumption.assessment }}</span
                                    >
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <p
                    lang="hy"
                    class="rounded-xl bg-background px-4 py-3 text-sm leading-relaxed text-foreground/80"
                >
                    {{ map.overall.comment }}
                </p>
            </div>
        </CollapsibleContent>
    </Collapsible>
</template>
