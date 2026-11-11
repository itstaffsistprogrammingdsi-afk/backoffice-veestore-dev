<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{ url: string | null; label: string; active: boolean }>;
}>();
</script>

<template>
    <nav class="d-flex flex-wrap gap-1" aria-label="Navigasi halaman">
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                class="btn btn-sm"
                :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                :aria-current="link.active ? 'page' : undefined"
                preserve-scroll
                v-html="link.label"
            />
            <span
                v-else
                class="btn btn-sm btn-outline-secondary disabled"
                aria-disabled="true"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
