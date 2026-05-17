import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import viteGlob from 'vite-plugin-glob';
import { resolve } from 'path';

export default defineConfig({
    build: {
        target: ['es2015', 'safari13'],
    },
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/ts/app.tsx'],
            refresh: true,
        }),
        viteGlob(),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/ts'),
            '@/components': resolve(__dirname, 'resources/ts/components'),
            '@/types': resolve(__dirname, 'resources/ts/types'),
            '@/layouts': resolve(__dirname, 'resources/ts/components/Layout'),
            '@/pages': resolve(__dirname, 'resources/ts/pages'),
            '@/utils': resolve(__dirname, 'resources/ts/utils'),
        },
    },
});
