<script setup lang="ts">
import { ref } from 'vue';
import type { Paragraph, Segment } from '@/lib/findings';

const props = defineProps<{
    paragraphs: Paragraph[];
    activeKey: string | null;
}>();

const emit = defineEmits<{
    select: [key: string];
}>();

const root = ref<HTMLElement | null>(null);

/** Clicking a passage covered by several findings cycles through them. */
function select(segment: Segment): void {
    const index = props.activeKey ? segment.keys.indexOf(props.activeKey) : -1;

    emit('select', segment.keys[(index + 1) % segment.keys.length]);
}

/** Offset of a finding's first passage from the top of the article, in pixels. */
function anchorTop(key: string): number | null {
    const element = root.value?.querySelector<HTMLElement>(
        `[data-keys~="${CSS.escape(key)}"]`,
    );

    if (!element || !root.value) {
        return null;
    }

    return (
        element.getBoundingClientRect().top -
        root.value.getBoundingClientRect().top
    );
}

function scrollTo(key: string): void {
    root.value
        ?.querySelector<HTMLElement>(`[data-keys~="${CSS.escape(key)}"]`)
        ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

defineExpose({ root, anchorTop, scrollTo });
</script>

<template>
    <div ref="root" lang="hy" class="reading-prose">
        <p
            v-for="(paragraph, index) in paragraphs"
            :key="index"
            class="relative"
        >
            <template v-for="(segment, position) in paragraph" :key="position">
                <mark
                    v-if="segment.keys.length > 0"
                    class="finding-mark"
                    role="button"
                    tabindex="-1"
                    :data-keys="segment.keys.join(' ')"
                    :data-type="segment.types[0]"
                    :data-overlap="segment.keys.length > 1"
                    :data-active="
                        activeKey !== null && segment.keys.includes(activeKey)
                    "
                    @click="select(segment)"
                    >{{ segment.text }}</mark
                >
                <template v-else>{{ segment.text }}</template>
            </template>
        </p>
    </div>
</template>
