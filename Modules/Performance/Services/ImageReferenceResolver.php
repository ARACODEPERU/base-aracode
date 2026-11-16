<?php

namespace Modules\Performance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Averigua dónde se usa cada imagen.
 *
 * Revisa las tablas que guardan imágenes (blog, cursos, tienda y CMS) y las
 * vistas Blade y hojas de estilo que las mencionan por nombre. Devuelve un
 * índice nombre-de-archivo => referencias, para que el inventario pueda mostrar
 * la ruta real de la página y agrupar las imágenes por pestaña.
 */
class ImageReferenceResolver
{
    /** Máximo de referencias que se guardan por imagen. */
    private const MAX_REFERENCES = 8;

    /**
     * Construye el índice de referencias para los nombres indicados.
     *
     * @param  array<int, string>  $wanted  Nombres de archivo en minúsculas.
     * @return array<string, array<int, array{label: string, url: ?string, kind: string}>>
     */
    public function build(array $wanted): array
    {
        $wanted = array_fill_keys(array_map('strtolower', $wanted), true);
        $index = [];

        $this->indexBlog($wanted, $index);
        $this->indexCourses($wanted, $index);
        $this->indexProducts($wanted, $index);
        $this->indexCms($wanted, $index);
        $this->indexViews($wanted, $index);
        $this->indexStyles($wanted, $index);

        return $index;
    }

    /**
     * @param  array<string, bool>  $wanted
     * @param  array<string, array<int, array{label: string, url: ?string, kind: string}>>  $index
     */
    private function indexBlog(array $wanted, array &$index): void
    {
        if (! $this->hasTable('blog_articles')) {
            return;
        }

        $rows = DB::table('blog_articles')
            ->where('status', true)
            ->select('id', 'url', 'title', 'imagen', 'content_text')
            ->get();

        foreach ($rows as $row) {
            $label = 'Blog: ' . Str::limit((string) $row->title, 60);
            $url = Route::has('blog_article') ? route('blog_article', $row->url) : null;

            foreach ($this->namesIn([$row->imagen, $row->content_text]) as $name) {
                if (isset($wanted[$name])) {
                    $this->add($index, $name, $label, $url, 'blog');
                }
            }
        }
    }

    /**
     * @param  array<string, bool>  $wanted
     * @param  array<string, array<int, array{label: string, url: ?string, kind: string}>>  $index
     */
    private function indexCourses(array $wanted, array &$index): void
    {
        if (! $this->hasTable('aca_courses')) {
            return;
        }

        $rows = DB::table('aca_courses')
            ->where('status', true)
            ->select('id', 'slug', 'description', 'image')
            ->get();

        foreach ($rows as $row) {
            $label = 'Curso: ' . Str::limit((string) $row->description, 60);

            $url = null;
            if ($row->slug && Route::has('course_url_slug')) {
                $url = route('course_url_slug', $row->slug);
            }

            foreach ($this->namesIn([$row->image]) as $name) {
                if (isset($wanted[$name])) {
                    $this->add($index, $name, $label, $url, 'curso');
                }
            }
        }
    }

    /**
     * @param  array<string, bool>  $wanted
     * @param  array<string, array<int, array{label: string, url: ?string, kind: string}>>  $index
     */
    private function indexProducts(array $wanted, array &$index): void
    {
        if (! $this->hasTable('onli_items')) {
            return;
        }

        $rows = DB::table('onli_items')
            ->select('id', 'name', 'image')
            ->get();

        foreach ($rows as $row) {
            $label = 'Producto: ' . Str::limit((string) $row->name, 60);

            foreach ($this->namesIn([$row->image]) as $name) {
                if (isset($wanted[$name])) {
                    $this->add($index, $name, $label, null, 'producto');
                }
            }
        }
    }

    /**
     * @param  array<string, bool>  $wanted
     * @param  array<string, array<int, array{label: string, url: ?string, kind: string}>>  $index
     */
    private function indexCms(array $wanted, array &$index): void
    {
        if (! $this->hasTable('cms_items')) {
            return;
        }

        $rows = DB::table('cms_items')
            ->where('content', 'like', '%<img%')
            ->select('id', 'content', 'description')
            ->limit(2000)
            ->get();

        foreach ($rows as $row) {
            $label = 'CMS: ' . Str::limit((string) ($row->description ?: 'Ítem #' . $row->id), 60);

            foreach ($this->namesIn([$row->content]) as $name) {
                if (isset($wanted[$name])) {
                    $this->add($index, $name, $label, null, 'cms');
                }
            }
        }
    }

    /**
     * @param  array<string, bool>  $wanted
     * @param  array<string, array<int, array{label: string, url: ?string, kind: string}>>  $index
     */
    private function indexViews(array $wanted, array &$index): void
    {
        foreach ($this->factories('view_directories', [resource_path('views/aracode')]) as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                if (! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $relative = $this->relativePath($file->getPathname());
                $key = Str::after($relative, 'resources/views/');
                $key = Str::beforeLast($key, '.blade.php');
                $url = config('performance.view_urls.' . $key);
                $label = 'Vista: ' . $key;

                foreach ($this->namesIn([File::get($file->getPathname())]) as $name) {
                    if (isset($wanted[$name])) {
                        $this->add($index, $name, $label, $url, 'vista');
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, bool>  $wanted
     * @param  array<string, array<int, array{label: string, url: ?string, kind: string}>>  $index
     */
    private function indexStyles(array $wanted, array &$index): void
    {
        $directories = config('performance.style_directories', [
            public_path('css'),
            public_path('themes'),
        ]);

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                if (! str_ends_with(strtolower($file->getFilename()), '.css')) {
                    continue;
                }

                $path = $this->relativePath($file->getPathname());
                $label = 'CSS: ' . $path;

                foreach ($this->namesIn([File::get($file->getPathname())]) as $name) {
                    if (isset($wanted[$name])) {
                        $this->add($index, $name, $label, '/' . Str::after($path, 'public/'), 'css');
                    }
                }
            }
        }
    }

    /**
     * Extrae los nombres de archivo de imagen que aparecen en los textos.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function namesIn(array $values): array
    {
        $names = [];

        foreach ($values as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            preg_match_all(
                '/[A-Za-z0-9_\-\.\/%]+\.(?:jpe?g|png|webp|gif)/i',
                $value,
                $matches
            );

            foreach ($matches[0] as $match) {
                $name = strtolower(basename(rawurldecode((string) parse_url($match, PHP_URL_PATH))));

                if ($name !== '') {
                    $names[$name] = true;
                }
            }
        }

        return array_keys($names);
    }

    /**
     * @param  array<string, array<int, array{label: string, url: ?string, kind: string}>>  $index
     */
    private function add(array &$index, string $name, string $label, ?string $url, string $kind): void
    {
        $index[$name] ??= [];

        foreach ($index[$name] as $reference) {
            if ($reference['label'] === $label && $reference['url'] === $url) {
                return;
            }
        }

        if (count($index[$name]) >= self::MAX_REFERENCES) {
            return;
        }

        $index[$name][] = [
            'label' => $label,
            'url'   => $url,
            'kind'  => $kind,
        ];
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    private function factories(string $configKey, array $default): array
    {
        $directories = config('performance.' . $configKey, $default);

        return is_array($directories) ? array_values(array_filter($directories)) : $default;
    }

    private function relativePath(string $absolute): string
    {
        $base = str_replace('\\', '/', base_path()) . '/';
        $path = str_replace('\\', '/', $absolute);

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    private function hasTable(string $table): bool
    {
        static $cache = [];

        if (! array_key_exists($table, $cache)) {
            try {
                $cache[$table] = \Illuminate\Support\Facades\Schema::hasTable($table);
            } catch (\Throwable $e) {
                $cache[$table] = false;
            }
        }

        return $cache[$table];
    }
}
