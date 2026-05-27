import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const assets = (...segments) => path.join(__dirname, 'Resources', 'assets', ...segments);

export default defineConfig({
    build: {
        outDir: '../../public/build-socialevents',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-socialevents',
            input: [
                assets('sass', 'app.scss'),
                assets('js', 'app.js'),
                assets('js', 'torneos-landing.js'),
            ],
            refresh: true,
        }),
    ],
});

//export const paths = [
//    'Modules/$STUDLY_NAME$/resources/assets/sass/app.scss',
//    'Modules/$STUDLY_NAME$/resources/assets/js/app.js',
//];