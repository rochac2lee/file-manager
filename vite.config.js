import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    server: {
        host: '0.0.0.0',
        port: 3000,
        hmr: {
            host: 'localhost',
        },
        // proxy: {
        //     '^/(?!@vite|@id|resources|node_modules|src|assets|__vite_ping)': {
        //         target: 'http://expertseg-backend:80',
        //         changeOrigin: true,
        //     },
        // },
    },
});
