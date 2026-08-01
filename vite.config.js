import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    // Fixa o dev server no IPv4 127.0.0.1 (mesmo host da aplicação). Sem isso o
    // Vite pode escutar apenas em [::1] (IPv6), fazendo o navegador falhar ao
    // buscar os assets e a página não carregar corretamente.
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/cercas.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
