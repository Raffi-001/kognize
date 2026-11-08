<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import QuotedPassage from '@/components/submission/QuotedPassage.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store as storeUserNote } from '@/routes/submissions/user-notes';

const props = defineProps<{
    submissionId: number;
    label: string;
}>();

defineEmits<{
    close: [];
}>();
</script>

<template>
    <div class="rounded-lg bg-surface p-3 shadow-sm" data-test="note-composer">
        <div class="mb-2 flex items-center justify-between">
            <p
                class="text-[10.5px] font-semibold tracking-wider text-community uppercase"
            >
                New community note
            </p>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="size-6"
                aria-label="Cancel note"
                @click="$emit('close')"
            >
                <X />
            </Button>
        </div>

        <QuotedPassage v-if="label" color="var(--community)" class="mb-2.5">
            {{ label }}
        </QuotedPassage>

        <Form
            v-bind="storeUserNote.form(submissionId)"
            :transform="(data) => ({ ...data, label: props.label })"
            :options="{ preserveScroll: true }"
            reset-on-success
            v-slot="{ errors, processing }"
            class="grid gap-2"
            @success="$emit('close')"
        >
            <Textarea
                v-focus
                name="text"
                placeholder="Add context, a source or a counter-argument…"
                class="min-h-24 border-0 bg-background shadow-xs dark:bg-background"
                required
                maxlength="2000"
            />
            <InputError :message="errors.text ?? errors.label" />
            <div class="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="$emit('close')"
                    >Cancel</Button
                >
                <Button type="submit" size="sm" :disabled="processing">
                    <Spinner v-if="processing" />
                    Publish note
                </Button>
            </div>
        </Form>
    </div>
</template>
