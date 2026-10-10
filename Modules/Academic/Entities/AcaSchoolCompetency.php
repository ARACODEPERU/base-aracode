<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Competencia CNEB de un area curricular del colegio (codigo SIAGIE 01,
 * 02, ...). El catalogo estandar se precarga automaticamente al abrir el
 * registro de notas si el colegio aun no lo tiene.
 */
class AcaSchoolCompetency extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'area_id',
        'area_name',
        'level',
        'code',
        'name',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolArea::class, 'area_id');
    }

    public function phrases(): HasMany
    {
        return $this->hasMany(AcaSchoolCompetencyPhrase::class, 'competency_id')
            ->orderBy('sort_order');
    }

    /**
     * Competencias estandar del CNEB (MINEDU) por nivel y area. La lista de
     * Primaria proviene del registro auxiliar de evaluacion SIAGIE
     * (currículo nacional 2017). Las competencias transversales (TIC y
     * Gestion autonomica) se registran como areas propias, igual que en
     * SIAGIE.
     */
    public static function standardCnebCompetencies(): array
    {
        return [
            AcaSchoolArea::LEVEL_PRIMARIA => [
                'Matemática' => [
                    ['code' => '01', 'name' => 'Resuelve problemas de cantidad'],
                    ['code' => '02', 'name' => 'Resuelve problemas de regularidad, equivalencia y cambio'],
                    ['code' => '03', 'name' => 'Resuelve problemas de forma, movimiento y localización'],
                    ['code' => '04', 'name' => 'Resuelve problemas de gestión de datos e incertidumbre'],
                ],
                'Comunicación' => [
                    ['code' => '01', 'name' => 'Se comunica oralmente en su lengua materna'],
                    ['code' => '02', 'name' => 'Lee diversos tipos de textos escritos en su lengua materna'],
                    ['code' => '03', 'name' => 'Escribe diversos tipos de textos en su lengua materna'],
                ],
                'Ciencia y Tecnología' => [
                    ['code' => '01', 'name' => 'Indaga mediante métodos científicos para construir sus conocimientos'],
                    ['code' => '02', 'name' => 'Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo'],
                    ['code' => '03', 'name' => 'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno'],
                ],
                'Personal Social' => [
                    ['code' => '01', 'name' => 'Construye su identidad'],
                    ['code' => '02', 'name' => 'Convive y participa democráticamente en la búsqueda del bien común'],
                    ['code' => '03', 'name' => 'Construye interpretaciones históricas'],
                    ['code' => '04', 'name' => 'Gestiona responsablemente el espacio y el ambiente'],
                    ['code' => '05', 'name' => 'Gestiona responsablemente los recursos económicos'],
                ],
                'Educación Religiosa' => [
                    ['code' => '01', 'name' => 'Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas'],
                    ['code' => '02', 'name' => 'Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa'],
                ],
                'Educación Física' => [
                    ['code' => '01', 'name' => 'Se desenvuelve de manera autónoma a través de su motricidad'],
                    ['code' => '02', 'name' => 'Asume una vida saludable'],
                    ['code' => '03', 'name' => 'Interactúa a través de sus habilidades sociomotrices'],
                ],
                'Arte y Cultura' => [
                    ['code' => '01', 'name' => 'Aprecia de manera crítica manifestaciones artístico-culturales'],
                    ['code' => '02', 'name' => 'Crea proyectos desde los lenguajes artísticos'],
                ],
                'Inglés como lengua extranjera' => [
                    ['code' => '01', 'name' => 'Se comunica oralmente en inglés como lengua extranjera'],
                    ['code' => '02', 'name' => 'Lee diversos tipos de textos escritos en inglés como lengua extranjera'],
                    ['code' => '03', 'name' => 'Escribe diversos tipos de textos en inglés como lengua extranjera'],
                ],
                'Se desenvuelve en entornos virtuales generados por las TIC' => [
                    ['code' => '01', 'name' => 'Se desenvuelve en entornos virtuales generados por las TIC'],
                ],
                'Gestiona su Aprendizaje de manera autónoma' => [
                    ['code' => '01', 'name' => 'Gestiona su Aprendizaje de manera autónoma'],
                ],
            ],

            AcaSchoolArea::LEVEL_SECUNDARIA => [
                'Matemática' => [
                    ['code' => '01', 'name' => 'Resuelve problemas de cantidad'],
                    ['code' => '02', 'name' => 'Resuelve problemas de regularidad, equivalencia y cambio'],
                    ['code' => '03', 'name' => 'Resuelve problemas de forma, movimiento y localización'],
                    ['code' => '04', 'name' => 'Resuelve problemas de gestión de datos e incertidumbre'],
                ],
                'Comunicación' => [
                    ['code' => '01', 'name' => 'Se comunica oralmente en su lengua materna'],
                    ['code' => '02', 'name' => 'Lee diversos tipos de textos escritos en su lengua materna'],
                    ['code' => '03', 'name' => 'Escribe diversos tipos de textos en su lengua materna'],
                ],
                'Ciencia y Tecnología' => [
                    ['code' => '01', 'name' => 'Indaga mediante métodos científicos para construir sus conocimientos'],
                    ['code' => '02', 'name' => 'Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo'],
                    ['code' => '03', 'name' => 'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno'],
                ],
                'Ciencias Sociales' => [
                    ['code' => '01', 'name' => 'Construye interpretaciones históricas'],
                    ['code' => '02', 'name' => 'Gestiona responsablemente el espacio y el ambiente'],
                    ['code' => '03', 'name' => 'Gestiona responsablemente los recursos económicos'],
                ],
                'Desarrollo Personal, Ciudadanía y Cívica' => [
                    ['code' => '01', 'name' => 'Construye su identidad'],
                    ['code' => '02', 'name' => 'Convive y participa democráticamente en la búsqueda del bien común'],
                ],
                'Educación Física' => [
                    ['code' => '01', 'name' => 'Se desenvuelve de manera autónoma a través de su motricidad'],
                    ['code' => '02', 'name' => 'Asume una vida saludable'],
                    ['code' => '03', 'name' => 'Interactúa a través de sus habilidades sociomotrices'],
                ],
                'Arte y Cultura' => [
                    ['code' => '01', 'name' => 'Aprecia de manera crítica manifestaciones artístico-culturales'],
                    ['code' => '02', 'name' => 'Crea proyectos desde los lenguajes artísticos'],
                ],
                'Inglés como lengua extranjera' => [
                    ['code' => '01', 'name' => 'Se comunica oralmente en inglés como lengua extranjera'],
                    ['code' => '02', 'name' => 'Lee diversos tipos de textos escritos en inglés como lengua extranjera'],
                    ['code' => '03', 'name' => 'Escribe diversos tipos de textos en inglés como lengua extranjera'],
                ],
                'Educación Religiosa' => [
                    ['code' => '01', 'name' => 'Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas'],
                    ['code' => '02', 'name' => 'Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa'],
                ],
                'Educación para el Trabajo' => [
                    ['code' => '01', 'name' => 'Gestiona proyectos de emprendimiento económico o social'],
                ],
                'Se desenvuelve en entornos virtuales generados por las TIC' => [
                    ['code' => '01', 'name' => 'Se desenvuelve en entornos virtuales generados por las TIC'],
                ],
                'Gestiona su Aprendizaje de manera autónoma' => [
                    ['code' => '01', 'name' => 'Gestiona su Aprendizaje de manera autónoma'],
                ],
            ],

            AcaSchoolArea::LEVEL_INICIAL => [
                'Desarrollo personal, social y comunicación' => [
                    ['code' => '01', 'name' => 'Construye su identidad'],
                    ['code' => '02', 'name' => 'Convive y participa democráticamente en la búsqueda del bien común'],
                ],
                'Ciencia y ambiente' => [
                    ['code' => '01', 'name' => 'Indaga mediante métodos científicos para construir sus conocimientos'],
                    ['code' => '02', 'name' => 'Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo'],
                ],
                'Psicomotricidad' => [
                    ['code' => '01', 'name' => 'Se desenvuelve de manera autónoma a través de su motricidad'],
                    ['code' => '02', 'name' => 'Asume una vida saludable'],
                    ['code' => '03', 'name' => 'Interactúa a través de sus habilidades sociomotrices'],
                ],
            ],
        ];
    }
}
