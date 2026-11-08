<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { analysisTypes, aspects } from '@/lib/analysis';
import { dashboard, login, register } from '@/routes';
import type { AnalysisTypeValue } from '@/types';

withDefaults(
    defineProps<{
        canRegister?: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const preview: { text: string; type: AnalysisTypeValue | null }[] = [
    { text: 'Եթե դու համաձայն չես կառավարության հետ, ', type: null },
    { text: 'ուրեմն դու հայրենիքի թշնամի ես', type: 'fallacy' },
    { text: '։ ', type: null },
    { text: 'Բոլորը գիտեն', type: 'questions' },
    {
        text: ', որ ընդդիմությունը վճարվում է արտասահմանից։ Մենք կամ պետք է ընդունենք այս բարեփոխումները հիմա, ',
        type: null,
    },
    { text: 'կամ էլ մեր երկիրը կկործանվի', type: 'rhetoric' },
    { text: '։', type: null },
];
</script>

<template>
    <Head title="Read between the lines" />

    <div class="relative min-h-svh overflow-hidden bg-background">
        <header
            class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5"
        >
            <div class="flex items-center gap-2">
                <AppLogo class="text-2xl" />
            </div>

            <nav class="flex items-center gap-2">
                <Button v-if="$page.props.auth.user" as-child>
                    <Link :href="dashboard()">Open dashboard</Link>
                </Button>
                <template v-else>
                    <Button variant="ghost" as-child>
                        <Link :href="login()">Log in</Link>
                    </Button>
                    <Button v-if="canRegister" as-child>
                        <Link :href="register()">I have an invite</Link>
                    </Button>
                </template>
            </nav>
        </header>

        <main
            class="relative mx-auto w-full max-w-6xl px-6 pt-12 pb-24 lg:pt-20"
        >
            <section class="mx-auto max-w-3xl text-center">
                <p
                    class="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-1 text-xs font-medium text-muted-foreground backdrop-blur"
                >
                    <span class="size-1.5 rounded-full bg-primary" />
                    Critical reading for Armenian public discourse
                </p>
                <h1
                    class="mt-6 font-display text-5xl leading-[1.02] tracking-tight text-balance sm:text-7xl"
                >
                    Read between the
                    <em class="text-brand-strong not-italic">lines</em>.
                </h1>
                <p
                    class="mx-auto mt-6 max-w-xl text-lg text-pretty text-muted-foreground"
                >
                    Paste a speech, a post or an article link.
                    {{ $page.props.name }} highlights logical fallacies and
                    rhetorical devices, and suggests the questions worth asking.
                </p>
                <div
                    class="mt-8 flex flex-wrap items-center justify-center gap-3"
                >
                    <Button size="lg" as-child>
                        <Link
                            :href="
                                $page.props.auth.user ? dashboard() : login()
                            "
                        >
                            Start analyzing
                            <ArrowRight />
                        </Link>
                    </Button>
                    <Button
                        v-if="canRegister && !$page.props.auth.user"
                        size="lg"
                        variant="outline"
                        as-child
                    >
                        <Link :href="register()">Redeem an invitation</Link>
                    </Button>
                </div>
            </section>

            <section
                class="mx-auto mt-16 max-w-4xl overflow-hidden rounded-xl bg-card shadow-[0_1px_2px_rgba(0,0,0,0.04),0_24px_48px_-24px_rgba(0,0,0,0.18)]"
                aria-label="Example analysis"
            >
                <div class="flex items-center gap-1.5 bg-surface px-4 py-3">
                    <span
                        v-for="dot in 3"
                        :key="dot"
                        class="size-2.5 rounded-full bg-muted-foreground/20"
                    />
                </div>
                <div
                    class="grid gap-6 p-6 md:grid-cols-[minmax(0,1fr)_16rem] md:p-8"
                >
                    <p lang="hy" class="reading-prose text-left">
                        <template v-for="(part, index) in preview" :key="index">
                            <mark
                                v-if="part.type"
                                class="finding-mark"
                                :data-type="part.type"
                                >{{ part.text }}</mark
                            >
                            <template v-else>{{ part.text }}</template>
                        </template>
                    </p>
                    <ul class="grid content-start gap-2 text-left">
                        <li
                            v-for="type in analysisTypes"
                            :key="type"
                            class="flex items-start gap-3 rounded-lg bg-surface p-3"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg"
                                :class="[
                                    aspects[type].softClass,
                                    aspects[type].textClass,
                                ]"
                            >
                                <component
                                    :is="aspects[type].icon"
                                    class="size-4"
                                />
                            </span>
                            <span>
                                <span class="block text-sm font-medium">{{
                                    aspects[type].label
                                }}</span>
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{ aspects[type].description }}</span
                                >
                            </span>
                        </li>
                    </ul>
                </div>
            </section>
        </main>
    </div>
</template>
