<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/reports';
import type { ReportableType } from '@/types';

defineProps<{
    reportableType: ReportableType;
    reportableId: number;
}>();

const open = defineModel<boolean>('open', { default: false });
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Report content</DialogTitle>
                <DialogDescription>
                    Tell us what's wrong. Our moderators review every report.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="store.form()"
                :transform="
                    (data) => ({
                        ...data,
                        reportable_type: reportableType,
                        reportable_id: reportableId,
                    })
                "
                :options="{ preserveScroll: true }"
                reset-on-success
                v-slot="{ errors, processing }"
                class="grid gap-4"
                @success="open = false"
            >
                <div class="grid gap-2">
                    <Textarea
                        name="reason"
                        placeholder="Describe the issue..."
                        class="min-h-28"
                        required
                    />
                    <InputError
                        :message="errors.reason ?? errors.reportable_id"
                    />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        Submit report
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
