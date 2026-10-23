/*
 * Build de producción SOLO de la entrada de la landing del torneo.
 *
 * La build completa del proyecto no puede ejecutarse en esta máquina porque
 * falta el paquete hermano `../editor-aracode` (enlace roto en node_modules).
 * Esta config compila el mismo recurso que sirve la landing, con el manifiesto
 * en el formato que espera Laravel, para poder revisarla con assets reales.
 */

import { defineConfig } from 'vite';
import path from 'node:path';

const root = process.cwd();
const entrada = path.resolve(root, 'Modules/Socialevents/Resources/assets/js/torneos-landing.js');

export default defineConfig({
    root,
    // Sin publicDir: Vite copiaba todo `public/` dentro de `public/build`.
    publicDir: false,
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: 'manifest.json',
        sourcemap: false,
        rollupOptions: {
            input: [entrada],
        },
    },
});
