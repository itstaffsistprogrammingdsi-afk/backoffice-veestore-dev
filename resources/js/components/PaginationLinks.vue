<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{ url: string | null; label: string; active: boolean }>;
}>();
</script>

<template>
    <nav aria-label="Navigasi halaman">
        <ul class="pagination pagination-sm mb-0">
            <li
                v-for="(link, index) in links"
                :key="index"
                class="page-item"
                :class="{ active: link.active, disabled: !link.url }"
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="page-link"
                    :aria-current="link.active ? 'page' : undefined"
                    preserve-scroll
                    v-html="link.label"
                />
                <span v-else class="page-link" aria-disabled="true" v-html="link.label" />
            </li>
        </ul>
    </nav>
</template>
