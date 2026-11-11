import 'bootstrap/dist/css/bootstrap.min.css';
import '@mdi/font/css/materialdesignicons.css';
import '../css/tokens.css';
import '../css/theme.css';
import '../css/components.css';
import '../css/vee-mascot.css';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { installLocaleTranslator } from './i18n';

createInertiaApp({
    title: (title) => (title ? `${title} - VeeStore` : 'VeeStore Master Data & Report'),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/**/*.vue'),
        ) as unknown as Promise<DefineComponent>,
    setup({ el, App, props, plugin }) {
        installLocaleTranslator((props.initialPage.props as { locale?: string }).locale);

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
