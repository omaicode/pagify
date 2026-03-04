import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const resolved = Object.fromEntries(
            Object.entries(pages).map(([path, module]) => [
                path.replace('./Pages/', '').replace('.vue', ''),
                module,
            ]),
        );

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
