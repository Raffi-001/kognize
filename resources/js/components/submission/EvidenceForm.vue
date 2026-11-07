<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Link2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { store as storeEvidence } from '@/routes/analysis-notes/evidence';
import type { StanceValue } from '@/types';

const props = defineProps<{
    questionId: number;
}>();

const emit = defineEmits<{
    done: [];
}>();

const form = useForm<{
    stance: StanceValue;
    url: string;
    quote: string;
    explanation: string;
}>({
    stance: 'supports',
    url: '',
    quote: '',
    explanation: '',
});

function setStance(value: unknown): void {
    if (value === 'supports' || value === 'refutes') {
        form.stance = value;
    }
}

function submit(): void {
    form.submit(storeEvidence(props.questionId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('done');
        },
    });
}
</script>

<template>
    <form
        class="grid gap-2.5 rounded-lg bg-background p-3"
        @submit.prevent="submit"
    >
        <p class="text-xs text-muted-foreground">
            Cite a source and copy the exact passage that answers the question.
            We check that the passage is on the page.
        </p>

        <ToggleGroup
            :model-value="form.stance"
            type="single"
            class="w-full rounded-lg bg-surface p-0.5"
            @update:model-value="setStance"
        >
            <ToggleGroupItem
                value="supports"
                class="flex-1 text-xs data-[state=on]:bg-background data-[state=on]:shadow-xs"
                >Supports the claim</ToggleGroupItem
            >
            <ToggleGroupItem
                value="refutes"
                class="flex-1 text-xs data-[state=on]:bg-background data-[state=on]:shadow-xs"
                >Refutes the claim</ToggleGroupItem
            >
        </ToggleGroup>

        <InputGroup class="border-0 bg-surface shadow-none dark:bg-surface">
            <InputGroupAddon>
                <Link2 />
            </InputGroupAddon>
            <InputGroupInput
                v-model="form.url"
                name="url"
                type="url"
                placeholder="https://source.am/document"
                aria-label="Source URL"
                required
            />
        </InputGroup>
        <InputError :message="form.errors.url" />

        <Textarea
            v-model="form.quote"
            name="quote"
            lang="hy"
            placeholder="The exact passage from the source…"
            class="min-h-20 border-0 bg-surface text-sm shadow-none dark:bg-surface"
            required
            maxlength="1000"
        />
        <InputError :message="form.errors.quote ?? form.errors.stance" />

        <Textarea
            v-model="form.explanation"
            name="explanation"
            placeholder="Why it answers the question (optional)"
            class="min-h-14 border-0 bg-surface text-sm shadow-none dark:bg-surface"
            maxlength="1000"
        />

        <div class="flex justify-end">
            <Button type="submit" size="sm" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                Submit evidence
            </Button>
        </div>
    </form>
</template>
