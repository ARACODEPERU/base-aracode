<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Origen de los testimonios sembrados.
     *
     * 'products'  → ultimos 20 registros de la tabla products (lo solicitado).
     *               Un producto no es un curso, por eso course_id queda null.
     * 'courses'   → ultimos 20 items de tienda de tipo curso
     *               (onli_items.entitie = 'Modules-Academic-Entities-AcaCourse'),
     *               que si traen curso y categoria. Cambia solo esta constante.
     */
    private const SEED_SOURCE = 'products';

    private const HOW_MANY = 5;

    private const PRODUCT_ENTITIE = 'App-Models-Product';

    /**
     * Comentarios y nombres de ejemplo. Son textos ficticios escritos aqui mismo:
     * el seed NO lee personas, usuarios ni alumnos de la base de datos.
     */
    private const SAMPLES = [
        ['author' => 'Rosa Elena Medina Campos', 'rating' => 5, 'comment' => 'Los casos practicos con empresas peruanas me ayudaron a aplicar lo aprendido en mi trabajo desde la primera semana. El material queda disponible siempre y eso me permitio repasar a mi ritmo.'],
        ['author' => 'Julio Cesar Palomino Ríos', 'rating' => 5, 'comment' => 'Docentes que ejercen en la profesion y explican sin relleno. Entendi por fin los temas que en la universidad me quedaron a medias.'],
        ['author' => 'Katherine Lozano Bermúdez', 'rating' => 4, 'comment' => 'La plataforma es muy practica y ordenada. Puedo seguir las clases desde el celular y descargar el material para repasar. Me hubiera gustado mas ejemplos en Excel.'],
        ['author' => 'Miguel Ángel Torres Huamán', 'rating' => 5, 'comment' => 'Aplique lo aprendido y logre ordenar la informacion contable de mi empresa. Ademas, el acompanamiento de los docentes responde rapido las dudas.'],
        ['author' => 'Paola Fernanda Gutiérrez Sáenz', 'rating' => 5, 'comment' => 'Termine el programa con mucha mas seguridad para tomar decisiones. Lo recomiendo a cualquier colega que quiera especializarse sin dejar de trabajar.'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('cms_testimonies') || !Schema::hasColumn('cms_testimonies', 'source')) {
            // La migracion de columnas debe correr antes que este seed.
            return;
        }

        $targets = $this->seedTargets();

        if (empty($targets)) {
            return;
        }

        $now = now();

        foreach ($targets as $index => $target) {
            // Idempotente: no duplica un testimonio ya asociado a ese item.
            $exists = DB::table('cms_testimonies')
                ->where('source', 'cms')
                ->where('entitie', $target['entitie'])
                ->where('item_id', $target['item_id'])
                ->exists();

            if ($exists) {
                continue;
            }

            $sample = self::SAMPLES[$index % count(self::SAMPLES)];

            DB::table('cms_testimonies')->insert([
                'item_id'             => $target['item_id'],
                'entitie'             => $target['entitie'],
                'student_id'          => null,
                'course_id'           => $target['course_id'],
                'title'               => $target['title'],
                'description'         => $sample['comment'],
                'rating'              => $sample['rating'],
                'source'              => 'cms',
                'approval_status'     => 'approved',
                'approved_at'         => $now,
                'approved_by'         => null,
                'author_name'         => $sample['author'],
                'consent_accepted'    => null,
                'consent_accepted_at' => null,
                'consent_version'     => null,
                'image'               => null,
                'video'               => null,
                'status'              => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('cms_testimonies') || !Schema::hasColumn('cms_testimonies', 'source')) {
            return;
        }

        $targets = $this->seedTargets();

        if (empty($targets)) {
            return;
        }

        DB::table('cms_testimonies')
            ->where('source', 'cms')
            ->whereNull('student_id')
            ->where(function ($query) use ($targets) {
                foreach ($targets as $target) {
                    $query->orWhere(function ($q) use ($target) {
                        $q->where('item_id', $target['item_id'])
                            ->where('entitie', $target['entitie']);
                    });
                }
            })
            ->delete();
    }

    /**
     * Destinos de los testimonios sembrados: item de tienda (si existe) o el
     * propio registro de products. Se toman los ultimos 20 del origen y se
     * eligen los primeros HOW_MANY.
     *
     * @return array<int, array{item_id: int|null, entitie: string|null, title: string, course_id: int|null}>
     */
    private function seedTargets(): array
    {
        if (self::SEED_SOURCE === 'courses') {
            if (!Schema::hasTable('onli_items')) {
                return [];
            }

            return DB::table('onli_items')
                ->where('entitie', 'Modules-Academic-Entities-AcaCourse')
                ->orderByDesc('id')
                ->limit(self::HOW_MANY)
                ->get(['id', 'item_id', 'name'])
                ->map(fn ($row) => [
                    'item_id' => (int) $row->id,
                    'entitie' => 'Modules\\Onlineshop\\Entities\\OnliItem',
                    'title' => $row->name ?: 'Testimonio de alumno',
                    'course_id' => (int) $row->item_id,
                ])
                ->all();
        }

        if (!Schema::hasTable('products')) {
            return [];
        }

        $products = DB::table('products')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'description']);

        if ($products->isEmpty()) {
            return [];
        }

        $productIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Si el producto tiene item de tienda, el testimonio se enlaza al item
        // (es lo que espera el resto del sistema). Si no, se enlaza al producto.
        $itemByProduct = Schema::hasTable('onli_items')
            ? DB::table('onli_items')
                ->where('entitie', self::PRODUCT_ENTITIE)
                ->whereIn('item_id', $productIds)
                ->get(['id', 'item_id'])
                ->keyBy('item_id')
            : collect();

        $targets = [];

        foreach ($products as $product) {
            if (count($targets) >= self::HOW_MANY) {
                break;
            }

            $item = $itemByProduct->get((int) $product->id);

            $targets[] = [
                'item_id' => $item ? (int) $item->id : (int) $product->id,
                'entitie' => $item ? 'Modules\\Onlineshop\\Entities\\OnliItem' : self::PRODUCT_ENTITIE,
                'title' => $product->description ?: 'Testimonio de alumno',
                'course_id' => null,
            ];
        }

        return $targets;
    }
};
