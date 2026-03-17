import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/admin.js'],
            publicDirectory: '../../../public',
            hotFile: '../../../public/hot',
            buildDirectory: 'build/admin',
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            vue: fileURLToPath(new URL('./node_modules/vue/dist/vue.esm-bundler.js', import.meta.url)),
            '@inertiajs/vue3': fileURLToPath(new URL('./node_modules/@inertiajs/vue3/dist/index.js', import.meta.url)),
            '@admin-theme': fileURLToPath(new URL('./resources/js', import.meta.url)),
            '@img': fileURLToPath(new URL('./resources/images', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        emptyOutDir: true,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('/grapesjs/') || id.includes('/backbone/') || id.includes('/underscore/')) {
                        return 'vendor-grapes';
                    }

                    if (id.includes('/@inertiajs/')) {
                        return 'vendor-inertia';
                    }

                    if (id.includes('/vue/') || id.includes('/@vue/')) {
                        return 'vendor-vue';
                    }

                    if (id.includes('/vue3-toastify/')) {
                        return 'vendor-toastify';
                    }

                    return 'vendor-misc';
                },
            },
        },
    }
});
