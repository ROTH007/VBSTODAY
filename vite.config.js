import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/react-entry.jsx', 'resources/js/cfocn-wizard-entry.jsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
});
