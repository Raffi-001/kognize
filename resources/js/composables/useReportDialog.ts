import { createInjectionState } from '@vueuse/core';
import { ref } from 'vue';
import type { ReportableType } from '@/types';

const [provideReportDialog, injectReportDialog] = createInjectionState(() => {
    const open = ref(false);
    const reportableType = ref<ReportableType>('submission');
    const reportableId = ref(0);

    function report(type: ReportableType, id: number): void {
        reportableType.value = type;
        reportableId.value = id;
        open.value = true;
    }

    return { open, reportableType, reportableId, report };
});

export { provideReportDialog };

export function useReportDialog(): NonNullable<
    ReturnType<typeof injectReportDialog>
> {
    const state = injectReportDialog();

    if (!state) {
        throw new Error(
            'useReportDialog() must be used inside a component that called provideReportDialog().',
        );
    }

    return state;
}
