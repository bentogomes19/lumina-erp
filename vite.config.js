import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/lumina/theme.css',
                'resources/js/app.js',
                'resources/js/filament/student-animations.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
