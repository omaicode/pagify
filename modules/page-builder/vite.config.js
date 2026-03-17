import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@page-builder-module': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        outDir: '../../public/build/page-builder',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                'editor-host': './resources/js/editor-host.js',
            },
        },
    },
});
