import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    resolve: (name) => {
        const corePages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const contentPages = import.meta.glob('../../../content/resources/js/Pages/**/*.vue', { eager: true });

        const resolved = {
            ...Object.fromEntries(
                Object.entries(corePages).map(([path, module]) => [
                    path.replace('./Pages/', '').replace('.vue', ''),
                    module,
                ]),
            ),
            ...Object.fromEntries(
                Object.entries(contentPages).map(([path, module]) => [
                    path.replace('../../../content/resources/js/Pages/', '').replace('.vue', ''),
                    module,
                ]),
            ),
        };

        const page = resolved[name];

        if (page === undefined) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#334155',
    },
});
