<script setup lang="ts">
import { Lock } from '@lucide/vue';
import { computed, useId } from 'vue';
import { badgeIcons, medalColors } from '@/lib/reputation';
import type { BadgeTierValue, BadgeValue } from '@/lib/reputation';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        badge: BadgeValue;
        tier: BadgeTierValue;
        earned?: boolean;
        size?: number;
        label?: string;
    }>(),
    { earned: true, size: 56, label: undefined },
);

const id = useId();
const colors = computed(() => medalColors[props.tier]);
const icon = computed(() => badgeIcons[props.badge]);

const CX = 32;
const CY = 30;

/** A closed polygon around the medal's center, alternating two radii. */
function polygon(
    points: number,
    outer: number,
    inner = outer,
    turn = -90,
): string {
    return Array.from({ length: points }, (_, index) => {
        const radius = index % 2 === 0 ? outer : inner;
        const angle = ((turn + (360 / points) * index) * Math.PI) / 180;

        return `${(CX + radius * Math.cos(angle)).toFixed(2)},${(CY + radius * Math.sin(angle)).toFixed(2)}`;
    }).join(' ');
}

/** Bronze is round, silver a hexagon and gold a rosette, so tiers differ by shape as well as color. */
const shape = computed(() => {
    switch (props.tier) {
        case 'gold':
            return { outer: polygon(28, 27, 24.5), inner: polygon(28, 21, 19) };
        case 'silver':
            return { outer: polygon(6, 27), inner: polygon(6, 21.5) };
        default:
            return null;
    }
});
</script>

<template>
    <span
        :class="
            cn(
                'relative inline-flex shrink-0',
                !earned && 'opacity-45 grayscale',
            )
        "
        :style="{ width: `${size}px`, height: `${(size * 76) / 64}px` }"
        role="img"
        :aria-label="label ?? `${tier} badge`"
        :data-badge="badge"
    >
        <svg
            viewBox="0 0 64 76"
            class="absolute inset-0 size-full"
            aria-hidden="true"
        >
            <defs>
                <linearGradient :id="`${id}-metal`" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" :stop-color="colors.light" />
                    <stop offset="100%" :stop-color="colors.mid" />
                </linearGradient>
                <linearGradient :id="`${id}-face`" x1="1" y1="0" x2="0" y2="1">
                    <stop offset="0%" :stop-color="colors.light" />
                    <stop
                        offset="100%"
                        :stop-color="colors.mid"
                        stop-opacity="0.85"
                    />
                </linearGradient>
            </defs>

            <!-- Ribbon tails -->
            <path
                d="M20 44 L13 73 L20.5 68 L26 74 L31 50 Z"
                :fill="colors.ribbon"
            />
            <path
                d="M44 44 L51 73 L43.5 68 L38 74 L33 50 Z"
                :fill="colors.ribbon"
                opacity="0.85"
            />

            <!-- Medal -->
            <template v-if="shape">
                <polygon
                    :points="shape.outer"
                    :fill="`url(#${id}-metal)`"
                    :stroke="colors.rim"
                    stroke-width="1.5"
                    stroke-linejoin="round"
                />
                <polygon
                    :points="shape.inner"
                    :fill="`url(#${id}-face)`"
                    :stroke="colors.rim"
                    stroke-opacity="0.55"
                    stroke-width="1"
                    stroke-linejoin="round"
                />
            </template>
            <template v-else>
                <circle
                    :cx="CX"
                    :cy="CY"
                    r="26"
                    :fill="`url(#${id}-metal)`"
                    :stroke="colors.rim"
                    stroke-width="1.5"
                />
                <circle
                    :cx="CX"
                    :cy="CY"
                    r="20.5"
                    :fill="`url(#${id}-face)`"
                    :stroke="colors.rim"
                    stroke-opacity="0.55"
                    stroke-width="1"
                    stroke-dasharray="1.5 2"
                />
            </template>

            <!-- Shine -->
            <ellipse
                :cx="CX - 6"
                :cy="CY - 12"
                rx="10"
                ry="4.5"
                fill="#fff"
                opacity="0.35"
                :transform="`rotate(-25 ${CX - 6} ${CY - 12})`"
            />
        </svg>

        <component
            :is="icon"
            class="absolute"
            :style="{
                color: colors.glyph,
                width: `${size * 0.36}px`,
                height: `${size * 0.36}px`,
                left: `${size * 0.32}px`,
                top: `${(size * 30) / 64 - size * 0.18}px`,
            }"
            :stroke-width="2.25"
        />

        <span
            v-if="!earned"
            class="absolute right-0 bottom-[18%] flex size-[34%] items-center justify-center rounded-full bg-background shadow-sm ring-1 ring-border"
        >
            <Lock class="size-[55%] text-muted-foreground" />
        </span>
    </span>
</template>
