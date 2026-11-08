<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import InteractiveDemo from '@/components/welcome/InteractiveDemo.vue';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister?: boolean;
    }>(),
    {
        canRegister: true,
    },
);
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
                    Kognize · critical reading for Armenian texts
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
                    Paste an article link, a post or an ad. Kognize maps how the
                    text argues: the reasoning gaps, the rhetoric and the
                    questions worth asking, so you can judge it yourself.
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

            <InteractiveDemo />
        </main>

        <footer
            class="mx-auto flex w-full max-w-6xl items-center justify-between border-t px-6 py-6 text-xs text-muted-foreground"
        >
            <span>© {{ new Date().getFullYear() }} Kognize</span>
            <span>kognize.me</span>
        </footer>
    </div>
</template>
