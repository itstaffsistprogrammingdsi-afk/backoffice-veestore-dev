<script setup lang="ts">
withDefaults(
    defineProps<{
        scale?: number;
        animated?: boolean;
        decorative?: boolean;
        priority?: boolean;
        variant?: 'full' | 'bust';
    }>(),
    {
        scale: 1,
        animated: false,
        decorative: false,
        priority: false,
        variant: 'full',
    },
);
</script>

<template>
    <div
        class="vee-mascot"
        :class="[`vee-mascot--${variant}`, { 'vee-mascot--animated': animated }]"
        :style="{ '--vee-scale': scale }"
        :role="decorative ? undefined : 'img'"
        :aria-hidden="decorative ? 'true' : undefined"
        :aria-label="decorative ? undefined : 'VEE, asisten data VeeStore'"
    >
        <div class="vee-mascot__art">
            <img
                class="vee-mascot__image"
                src="/assets/img/vee-mascot-3d.png"
                alt=""
                width="1086"
                height="1448"
                :loading="priority ? 'eager' : 'lazy'"
                :fetchpriority="priority ? 'high' : 'auto'"
                decoding="async"
            />
        </div>
    </div>
</template>
