<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{ url: string | null; label: string; active: boolean }>;
}>();
</script>

<template>
    <div class="d-flex flex-wrap gap-1">
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                class="btn btn-sm"
                :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                preserve-scroll
                v-html="$t(link.label)"
            />
            <span v-else class="btn btn-sm btn-outline-secondary disabled" v-html="$t(link.label)" />
        </template>
    </div>
</template>
