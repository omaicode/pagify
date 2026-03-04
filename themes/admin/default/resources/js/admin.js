import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import Toastify, { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

import.meta.glob([
    '../images/**'
]);

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
            .use(Toastify, {
                autoClose: 1800,
                position: toast.POSITION.TOP_RIGHT,
                hideProgressBar: true,
                closeOnClick: true,
                pauseOnHover: true,
            })
            .mount(el);
    },
    progress: {
        color: '#334155',
    },
});
