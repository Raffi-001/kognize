<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { notificationKinds } from '@/lib/notifications';
import { edit, update } from '@/routes/notification-preferences';
import type {
    BreadcrumbItem,
    NotificationKindOption,
    NotificationKindValue,
} from '@/types';

defineOptions({
    layout: [
        [
            AppLayout,
            {
                breadcrumbs: [
                    { title: 'Notification settings', href: edit() },
                ] satisfies BreadcrumbItem[],
            },
        ],
        SettingsLayout,
    ],
});

const props = defineProps<{
    kinds: NotificationKindOption[];
    muted: NotificationKindValue[];
    digest: boolean;
}>();

const form = useForm({
    muted: [...props.muted],
    digest: props.digest,
});

function toggle(kind: NotificationKindValue, enabled: boolean): void {
    form.muted = enabled
        ? form.muted.filter((value) => value !== kind)
        : [...form.muted, kind];
}

function submit(): void {
    form.put(update().url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notification settings" />

    <h1 class="sr-only">Notification settings</h1>

    <form class="space-y-10" @submit.prevent="submit">
        <div class="space-y-6">
            <Heading
                variant="small"
                title="In-app notifications"
                description="Choose what shows up under the bell."
            />

            <ul class="divide-y rounded-lg border">
                <li
                    v-for="kind in kinds"
                    :key="kind.value"
                    class="flex items-center gap-3 px-4 py-3"
                >
                    <span
                        class="flex size-8 shrink-0 items-center justify-center rounded-full"
                        :class="notificationKinds[kind.value].class"
                    >
                        <component
                            :is="notificationKinds[kind.value].icon"
                            class="size-4"
                        />
                    </span>
                    <div class="grid min-w-0 flex-1 gap-0.5">
                        <Label :for="`notify-${kind.value}`">{{
                            kind.label
                        }}</Label>
                        <p class="text-xs text-muted-foreground">
                            {{ kind.description }}
                        </p>
                    </div>
                    <Switch
                        :id="`notify-${kind.value}`"
                        :model-value="!form.muted.includes(kind.value)"
                        :data-test="`notification-kind-${kind.value}`"
                        @update:model-value="
                            (enabled: boolean) => toggle(kind.value, enabled)
                        "
                    />
                </li>
            </ul>
            <InputError :message="form.errors.muted" />
        </div>

        <div class="space-y-6">
            <Heading
                variant="small"
                title="Email"
                description="Get a short daily email of anything you haven't read yet."
            />
            <div
                class="flex items-center justify-between gap-4 rounded-lg border px-4 py-3"
            >
                <div class="grid gap-0.5">
                    <Label for="notification-digest">Daily summary</Label>
                    <p class="text-xs text-muted-foreground">
                        Sent each morning, and only on days with something new.
                    </p>
                </div>
                <Switch
                    id="notification-digest"
                    v-model="form.digest"
                    data-test="notification-digest"
                />
            </div>
        </div>

        <Button
            type="submit"
            :disabled="form.processing"
            data-test="save-notification-preferences"
            >Save</Button
        >
    </form>
</template>
