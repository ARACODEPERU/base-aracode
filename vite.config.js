import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: "resources/js/app.js",
            ssr: "resources/js/ssr.js",
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
            'Modules': path.resolve(__dirname, './Modules'),
            '@Public': path.resolve(__dirname, './public'),
        },
    },

    // 🔧 Build optimizations para producción
    build: {
        target: 'es2018',              // Reduce código legacy innecesario
        minify: 'esbuild',             // Más rápido que terser
        cssCodeSplit: true,            // Separa CSS para mejor carga
        sourcemap: false,              // Solo en producción (como ya tienes)
        emptyOutDir: true,             // Limpia dist antes de compilar
    },

    // ⚙️ Server settings (para desarrollo)
    server: {
        sourcemap: true,
        cors: true,
    },

    // ⚡️ Cache persistente (opcional pero útil)
    cacheDir: 'node_modules/.vite_cache',

    // ⚡️ Optimización de dependencias
    optimizeDeps: {
        include: ['axios'], // agrega aquí otros paquetes pesados si los usas
    },
});
