import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 4's Sass predates the Dart Sass module system.
                quietDeps: true,
                silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'slash-div', 'mixed-decls', 'if-function'],
            },
        },
    },
    build: {
        // The lazily loaded CKEditor chunk is about 830 kB.
        chunkSizeWarningLimit: 900,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
