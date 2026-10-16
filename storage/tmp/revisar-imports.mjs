// Revision: todos los imports relativos del modulo Commercial deben resolver a un archivo real.
import { readFileSync, existsSync, readdirSync, statSync } from "node:fs";
import { dirname, resolve, relative } from "node:path";

const raiz = "Modules/Commercial/Resources/assets/js";

const archivos = [];
const recorrer = (dir) => {
    for (const entrada of readdirSync(dir)) {
        const path = resolve(dir, entrada);
        if (statSync(path).isDirectory()) recorrer(path);
        else if (/\.(vue|js|mjs)$/.test(entrada)) archivos.push(path);
    }
};
recorrer(raiz);

const candidatos = (p) => [p, p + ".js", p + ".vue", p + ".mjs", p + "/index.js"];
let fallos = 0;

for (const archivo of archivos) {
    const fuente = readFileSync(archivo, "utf8");
    const regex = /(?:from|import)\s+["'](\.[^"']*)["']/g;
    let match;

    while ((match = regex.exec(fuente)) !== null) {
        const objetivo = resolve(dirname(archivo), match[1]);
        if (!candidatos(objetivo).some((c) => existsSync(c))) {
            console.log("NO RESUELVE:", relative(process.cwd(), archivo), "->", match[1]);
            fallos++;
        }
    }
}

console.log(fallos ? `${fallos} import(s) roto(s)` : `OK: ${archivos.length} archivos, todos los imports relativos resuelven`);
process.exitCode = fallos ? 1 : 0;
