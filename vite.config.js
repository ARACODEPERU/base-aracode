import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const editorRoot = path.resolve(__dirname, "../editor-aracode");

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
                ...(useEditorSource
                    ? {
                          "@elmerrodriguez/editor-aracode": path.join(
                              editorRoot,
                              "src/index.js"
                          ),
                          "@elmerrodriguez/editor-aracode/dist/aracode-editor.css":
                              path.resolve(__dirname, "resources/js/stubs/empty.css"),
                      }
                    : {}),
            },
        },
        optimizeDeps: {
            exclude: ["@elmerrodriguez/editor-aracode"],
        },
        build: {
            sourcemap: false,
        },
        server: {
            sourcemap: true,
            cors: true,
            fs: {
                allow: [path.resolve(__dirname, "..")],
            },
            watch: {
                ignored: ["**/node_modules/**", "!**/editor-aracode/**"],
            },
        },
    };
});
