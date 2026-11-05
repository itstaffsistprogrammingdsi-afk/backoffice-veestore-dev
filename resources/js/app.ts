import 'bootstrap/dist/css/bootstrap.min.css';
import '@mdi/font/css/materialdesignicons.css';
import '../css/theme.css';
import '../css/vee-mascot.css';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { intlLocale, translate } from '@/composables/i18n';

createInertiaApp({
    title: (title) => (title ? `${title} - VeeStore` : 'VeeStore Backoffice'),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/**/*.vue'),
        ) as unknown as Promise<DefineComponent>,
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.config.globalProperties.$t = translate;
        app.config.globalProperties.$localeTag = intlLocale;
        app
            .use(plugin)
            .mount(el);
    },
});
