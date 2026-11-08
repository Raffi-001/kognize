<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { CircleCheck, Clock, Mail, Send } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/invitations';
import type { BreadcrumbItem, Invitation } from '@/types';

defineOptions({
    layout: [
        AppLayout,
        {
            breadcrumbs: [
                {
                    title: 'Invitations',
                    href: index(),
                },
            ] satisfies BreadcrumbItem[],
        },
    ],
});

defineProps<{
    invitations: Invitation[];
}>();

const page = usePage();
const meter = computed(() => page.props.auth.usage?.invitations);
</script>

<template>
    <Head title="Invitations" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6">
        <PageHeader
            title="Invitations"
            description="Kognize grows through trusted invitations. Each code works once, for the email address you send it to."
        />

        <div class="grid max-w-3xl gap-8">
            <section class="overflow-hidden rounded-xl bg-surface">
                <div class="flex items-center justify-between gap-4 px-5 pt-5">
                    <div>
                        <h2 class="font-medium">Invite someone</h2>
                        <p class="text-sm text-muted-foreground">
                            They'll receive an email with their personal code.
                        </p>
                    </div>
                    <div v-if="meter" class="text-right">
                        <p
                            class="font-display text-3xl leading-none tabular-nums"
                        >
                            {{ meter.remaining }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            of {{ meter.limit }} left
                        </p>
                    </div>
                </div>

                <Form
                    v-bind="store.form()"
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    v-slot="{ errors, processing }"
                    class="grid gap-2 p-5"
                >
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <InputGroup
                            class="h-10 flex-1 border-0 bg-background shadow-xs dark:bg-background"
                        >
                            <InputGroupAddon>
                                <Mail />
                            </InputGroupAddon>
                            <InputGroupInput
                                name="email"
                                type="email"
                                autocomplete="off"
                                placeholder="colleague@example.com"
                                aria-label="Email address"
                                required
                                :disabled="meter?.remaining === 0"
                            />
                        </InputGroup>
                        <Button
                            type="submit"
                            size="lg"
                            :disabled="processing || meter?.remaining === 0"
                            data-test="send-invitation-button"
                        >
                            <Spinner v-if="processing" />
                            <Send v-else />
                            Send invitation
                        </Button>
                    </div>
                    <InputError :message="errors.email ?? errors.limit" />
                    <p
                        v-if="meter?.remaining === 0"
                        class="text-sm text-muted-foreground"
                    >
                        You've used all of your invitations. Thanks for helping
                        the community grow!
                    </p>
                </Form>
            </section>

            <section class="grid gap-3">
                <h2 class="text-sm font-medium text-muted-foreground">
                    Sent invitations
                </h2>

                <ul
                    v-if="invitations.length > 0"
                    class="grid grid-cols-1 gap-0.5 rounded-xl bg-surface p-1.5"
                >
                    <li
                        v-for="invitation in invitations"
                        :key="invitation.id"
                        class="flex items-center gap-4 rounded-lg px-4 py-3 transition-colors hover:bg-background"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ invitation.email }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Sent {{ invitation.created_at_human }}
                                <template v-if="invitation.is_used">
                                    · joined as {{ invitation.invitee?.name }}
                                    {{ invitation.used_at_human }}
                                </template>
                            </p>
                        </div>
                        <code
                            class="rounded-md bg-background px-2 py-1 font-mono text-xs tracking-widest"
                            >{{ invitation.code }}</code
                        >
                        <span
                            v-if="invitation.is_used"
                            class="flex items-center gap-1 text-xs font-medium text-brand-strong"
                            ><CircleCheck class="size-3.5" /> Joined</span
                        >
                        <span
                            v-else
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                            ><Clock class="size-3.5" /> Pending</span
                        >
                    </li>
                </ul>

                <Empty v-else class="rounded-xl bg-surface">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <Mail />
                        </EmptyMedia>
                        <EmptyTitle>No invitations sent yet</EmptyTitle>
                        <EmptyDescription>
                            Invite people whose judgement you trust.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            </section>
        </div>
    </div>
</template>
