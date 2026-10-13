<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Codigo del parametro de tipo de negocio o empresa. */
    private const PARAMETER_CODE = 'P000009';

    /** Descripcion del parametro (la de la migracion base). */
    private const DESCRIPTION = 'Tipo de negocio o empresa, para ventas en linea';

    /** Tipo de control: lista desplegable. */
    private const CONTROL_TYPE = 'sa';

    /**
     * Catalogo original definido en la migracion base
     * (2023_07_07_172256_create_parameters_table). Es lo que se restaura al
     * revertir esta migracion.
     */
    private const CATALOGO_ANTERIOR = [
        ['value' => '1', 'label' => 'Cursos y capacitaciones'],
        ['value' => '2', 'label' => 'Productos'],
        ['value' => '3', 'label' => 'Productos con Especificaciónes'],
        ['value' => '4', 'label' => 'Servicios'],
        ['value' => '99', 'label' => 'Todos'],
    ];

    /**
     * Catalogo vigente que debe ofrecer P000009: agrega el modo Colegio (5) y
     * Colegio y capacitaciones (6), y deja fuera la opcion 99 (Todos), que
     * ningun modulo del sistema reconoce.
     *
     * @var array<int, array<string, string>>
     */
    private const CATALOGO = [
        ['value' => '1', 'label' => 'Cursos'],
        ['value' => '2', 'label' => 'Productos'],
        ['value' => '3', 'label' => 'Productos con Especificaciónes'],
        ['value' => '4', 'label' => 'Servicios'],
        ['value' => '5', 'label' => 'Colegio'],
        ['value' => '6', 'label' => 'Colegio y capacitaciones'],
    ];

    /**
     * Reemplaza el contenido de la lista del parametro P000009.
     *
     * Solo escribe la columna json_query_data: no toca la descripcion, el tipo
     * de control ni el valor seleccionado (value_default), porque las pantallas
     * usan ese valor para decidir que bloques mostrar (Dashboard, CRM, CMS,
     * Onlineshop y el menu Academico) y cambiarlo por iniciativa propia
     * alteraria el comportamiento del sistema.
     *
     * Ojo: si una instalacion tenia seleccionado el valor 99 (Todos), al
     * quedar fuera del catalogo el selector aparecera vacio hasta elegir una
     * opcion valida. Es el unico valor que se retira.
     *
     * Idempotente: si el catalogo ya es el esperado no toca el registro (asi
     * updated_at no cambia al volver a ejecutarla).
     */
    public function up(): void
    {
        $this->reemplazarCatalogo(self::CATALOGO);
    }

    /**
     * Restaura el catalogo original de la migracion base. Tampoco toca el
     * valor seleccionado: revertir no cambia lo publicado.
     */
    public function down(): void
    {
        $this->reemplazarCatalogo(self::CATALOGO_ANTERIOR);
    }

    /**
     * Escribe json_query_data con el catalogo indicado, creando el parametro
     * con sus valores base si no existiera.
     */
    private function reemplazarCatalogo(array $catalogo): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $parameter = Parameter::where('parameter_code', self::PARAMETER_CODE)->first();

        if (! $parameter) {
            Parameter::create([
                'parameter_code' => self::PARAMETER_CODE,
                'description' => self::DESCRIPTION,
                'control_type' => self::CONTROL_TYPE,
                'json_query_data' => $this->codificar($catalogo),
                'value_default' => null,
            ]);

            return;
        }

        if ($this->yaTieneCatalogo($parameter->json_query_data, $catalogo)) {
            return;
        }

        $parameter->update(['json_query_data' => $this->codificar($catalogo)]);
    }

    /**
     * true si el JSON guardado ya es exactamente el catalogo esperado (mismos
     * valores, mismas etiquetas y mismo orden).
     */
    private function yaTieneCatalogo(?string $jsonActual, array $catalogo): bool
    {
        $actual = json_decode((string) $jsonActual, true);

        if (! is_array($actual) || count($actual) !== count($catalogo)) {
            return false;
        }

        foreach ($catalogo as $indice => $opcion) {
            $fila = $actual[$indice] ?? null;

            if (! is_array($fila)
                || ($fila['value'] ?? null) !== $opcion['value']
                || ($fila['label'] ?? null) !== $opcion['label']) {
                return false;
            }
        }

        return true;
    }

    /** Serializa el catalogo al formato que guarda la columna json_query_data. */
    private function codificar(array $catalogo): string
    {
        return json_encode($catalogo, JSON_UNESCAPED_UNICODE);
    }
};
