/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue';

    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
    export default component;
}
import type { translate } from '@/composables/i18n';

declare module 'vue' {
    interface ComponentCustomProperties {
        $t: typeof translate;
        $localeTag: () => string;
    }
}
