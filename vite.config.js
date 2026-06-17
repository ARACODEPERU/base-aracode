import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";
import { fileURLToPath } from "url";
import fs from "fs";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function resolveEditorRoot() {
    const candidates = [
        path.resolve(__dirname, "../editor-aracode"),
        path.resolve(__dirname, "public/EditorAracode"),
        path.resolve(__dirname, "node_modules/@elmerrodriguez/editor-aracode"),
    ];

    for (const root of candidates) {
        if (fs.existsSync(path.join(root, "src/index.js"))) {
            return root;
        }
        if (fs.existsSync(path.join(root, "dist/aracode-editor.css"))) {
            return root;
        }
    }

    return candidates[0];
}

function resolveEditorCssBundle(root) {
    const distCss = path.join(root, "dist/aracode-editor.css");
    if (fs.existsSync(distCss)) {
        return distCss;
    }

    return path.join(root, "src/styles/editor.css");
}

function resolveEditorDarkCss(root) {
    const distDark = path.join(root, "dist/theme-dark.css");
    if (fs.existsSync(distDark)) {
        return distDark;
    }

    const darkCss = path.join(root, "src/styles/themes/dark.css");
    if (fs.existsSync(darkCss)) {
        return darkCss;
    }

    const emptyStub = path.resolve(__dirname, "resources/js/stubs/empty.css");
    return emptyStub;
}

const editorRoot = resolveEditorRoot();
const editorBundleCss = resolveEditorCssBundle(editorRoot);
const editorDarkCss = resolveEditorDarkCss(editorRoot);
const editorEntryJs = fs.existsSync(path.join(editorRoot, "src/index.js"))
    ? path.join(editorRoot, "src/index.js")
    : path.join(editorRoot, "dist/aracode-editor.es.js");

export default defineConfig(({ command }) => {
    const useEditorSource = command === "serve";

    return {
        plugins: [
            laravel({
                input: [
                    "resources/js/app.js",
                    "Modules/Socialevents/Resources/assets/js/torneos-landing.js",
                ],
                ssr: "resources/js/ssr.js",
                refresh: true,
                detectTls: false,
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
                Modules: path.resolve(__dirname, "./Modules"),
                "@Public": path.resolve(__dirname, "./public"),
                "@stores": path.resolve(__dirname, "./resources/js/stores"),
                "@editor-aracode-styles": useEditorSource
                    ? path.resolve(__dirname, "resources/js/editor-aracode-dev-styles.js")
                    : path.resolve(__dirname, "resources/js/editor-aracode-prod-styles.js"),
                ...(useEditorSource
                    ? {
                          "@elmerrodriguez/editor-aracode$": editorEntryJs,
                          "@elmerrodriguez/editor-aracode/style.css": editorBundleCss,
                          "@elmerrodriguez/editor-aracode/theme-dark.css": editorDarkCss,
                      }
                    : {
                          "@elmerrodriguez/editor-aracode/style.css": editorBundleCss,
                          "@elmerrodriguez/editor-aracode/theme-dark.css": editorDarkCss,
                      }),
            },
        },
        optimizeDeps: {
            exclude: ["@elmerrodriguez/editor-aracode"],
        },
        build: {
            sourcemap: false,
        },
        server: {
            host: "127.0.0.1",
            port: 5173,
            strictPort: true,
            origin: "http://127.0.0.1:5173",
            sourcemap: true,
            cors: true,
            hmr: {
                host: "127.0.0.1",
            },
            fs: {
                allow: [
                    path.resolve(__dirname, ".."),
                    editorRoot,
                ],
            },
            watch: {
                ignored: ["**/node_modules/**"],
            },
        },
    };
});
