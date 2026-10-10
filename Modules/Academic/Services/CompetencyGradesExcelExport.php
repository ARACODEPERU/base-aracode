<?php

namespace Modules\Academic\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolGradeCompetency;
use Modules\Academic\Entities\AcaSchoolYear;

/**
 * Genera el registro auxiliar de evaluacion en Excel con la misma
 * estructura del archivo SIAGIE de referencia:
 *   - Hoja "Generalidades": datos de la I.E. y areas del nivel
 *   - Hoja "Parametros": codigos internos (nivel B0, periodo B1, ...)
 *   - Una hoja por area: ID | Cod. Estudiante | Nombres | [NL + Conclusion
 *     descriptiva por competencia] + LEYENDA al pie
 */
class CompetencyGradesExcelExport
{
    /** Codigo de nivel SIAGIE por nivel CNEB. */
    private const SIAGIE_LEVEL_CODES = [
        'inicial' => 'A0',
        'primaria' => 'B0',
        'secundaria' => 'F0',
    ];

    /** Codigo de area SIAGIE por nombre corto (fallback: secuencia). */
    private const SIAGIE_AREA_CODES = [
        'matemática' => ['063', 'MATE'],
        'comunicación' => ['0005', 'COMU'],
        'ciencia y tecnología' => ['0004', 'CIENC TEC'],
        'personal social' => ['067', 'PPSS'],
        'educación religiosa' => ['035', 'EREL'],
        'arte y cultura' => ['0001', 'ART Y CULT'],
        'educación física' => ['0034', 'ED FISICA'],
        'inglés como lengua extranjera' => ['0041', 'INGLE'],
        'ciencias sociales' => ['0059', 'CCSS'],
        'desarrollo personal, ciudadanía y cívica' => ['0063', 'DPCC'],
        'educación para el trabajo' => ['0075', 'EPT'],
        'se desenvuelve en entornos virtuales generados por las tic' => ['0006', 'DESEN TIC'],
        'gestiona su aprendizaje de manera autónoma' => ['0007', 'GEST AUTO'],
        'desarrollo personal, social y comunicación' => ['0033', 'DPSC'],
        'ciencia y ambiente' => ['0032', 'CIEN AMB'],
        'psicomotricidad' => ['0035', 'PSICO'],
    ];

    private array $students = [];

    private array $areas = [];

    private array $competencyByArea = [];

    /** @var \Illuminate\Support\Collection registros agrupados por matricula|competencia */
    private $records;

    private object $section;

    private object $school;

    private string $yearNumber;

    private string $scale;

    private int $bimester;

    public function __construct(private int $sectionId, int $bimester)
    {
        $this->bimester = $bimester;
        $this->load();
    }

    private function load(): void
    {
        $section = \Modules\Academic\Entities\AcaSchoolSection::with('grade.level')->findOrFail($this->sectionId);
        $this->section = $section;
        $this->school = $section->school;

        $year = AcaSchoolYear::where('status', AcaSchoolYear::STATUS_ACTIVE)->orderBy('id')->first()
            ?? AcaSchoolYear::orderBy('id')->first();
        $this->yearNumber = (string) ($year?->year ?? now()->format('Y'));
        $this->scale = $section->grade?->level?->evaluationScale() ?? AcaSchoolGradeCompetency::SCALE_VIGESIMAL;

        // Areas y competencias del nivel (mismo origen que la grilla)
        $levelCode = strtolower(trim((string) ($section->grade?->level?->code ?? 'primaria')));
        $areas = \Modules\Academic\Entities\AcaSchoolArea::where('school_id', $section->school_id)
            ->where('level', $levelCode)
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->areas = $areas->isEmpty()
            ? collect(\Modules\Academic\Entities\AcaSchoolArea::standardCnebAreas()[$levelCode])
                ->map(fn ($name) => (object) ['id' => null, 'name' => $name])->all()
            : $areas->all();

        $competencies = \Modules\Academic\Entities\AcaSchoolCompetency::where('school_id', $section->school_id)
            ->where('level', $levelCode)
            ->where('status', true)
            ->orderBy('sort_order')
            ->get(['id', 'area_name', 'code', 'name']);

        foreach ($this->areas as $area) {
            $this->competencyByArea[$area->name] = $competencies
                ->filter(fn ($c) => $c->area_name === $area->name)
                ->values();
        }

        $enrollments = AcaSchoolEnrollment::with('student.person')
            ->where('section_id', $this->sectionId)
            ->where('year_id', $year?->id)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->orderBy('id')
            ->get();

        $this->students = $enrollments->all();

        $this->records = AcaSchoolGradeCompetency::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->where('bimester', $this->bimester)
            ->get()
            ->groupBy(fn ($r) => $r->enrollment_id.'|'.$r->competency_id);
    }

    public function download(): StreamedResponse
    {
        $filename = $this->filename();

        return response()->streamDownload(function () {
            (new Xlsx($this->build()))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Genera el libro y lo guarda en la ruta indicada (uso interno/pruebas).
     */
    public function saveTo(string $path): void
    {
        (new Xlsx($this->build()))->save($path);
    }

    /**
     * Construye el libro completo: Generalidades + Parametros + hoja por area.
     */
    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $this->buildGeneralidades($spreadsheet);
        $this->buildParametros($spreadsheet);

        foreach ($this->areas as $area) {
            $this->buildAreaSheet($spreadsheet, $area);
        }

        // El libro abre en Generalidades
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function filename(): string
    {
        $levelCode = self::SIAGIE_LEVEL_CODES[strtolower(trim((string) ($this->section->grade?->level?->code ?? 'primaria')))] ?? 'B0';
        $modular = preg_replace('/[^0-9A-Za-z]/', '', (string) ($this->school->modular_code ?? '')).($this->school->is_default ? '0' : '');
        $period = 'B'.$this->bimester;

        return "RegNotas_{$modular}_20_{$levelCode}{$this->yearNumber}_{$period}_{$this->sectionId}.xlsx";
    }

    // ------------------------------------------------------------------
    // Hoja Generalidades
    // ------------------------------------------------------------------
    private function buildGeneralidades(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Generalidades');

        $levelName = strtoupper($this->section->grade?->level?->name ?? 'PRIMARIA');
        $periodName = ['1' => 'PRIMER BIMESTRE', '2' => 'SEGUNDO BIMESTRE', '3' => 'TERCER BIMESTRE', '4' => 'CUARTO BIMESTRE'][$this->bimester];

        $sheet->setCellValue('B2', 'DATOS GENERALES :');
        $sheet->setCellValue('B4', 'Institución educativa :');
        $sheet->setCellValue('B5', 'Código modular - Anexo :');
        $sheet->setCellValue('E5', ($this->school->modular_code ?? '').'-0');
        $sheet->setCellValue('G5', 'Nivel :');
        $sheet->setCellValue('H5', $levelName);
        $sheet->setCellValue('B6', 'Nombre :');
        $sheet->setCellValue('C6', $this->school->name);
        $sheet->setCellValue('B7', 'Datos referentes al registro de notas :');
        $sheet->setCellValue('B8', 'Año académico :');
        $sheet->setCellValue('D8', $this->yearNumber);
        $sheet->setCellValue('B9', 'Diseño curricular :');
        $sheet->setCellValue('D9', 'CURRÍCULO NACIONAL 2017');
        $sheet->setCellValue('B10', 'Período de evaluación :');
        $sheet->setCellValue('D10', $periodName);
        $sheet->setCellValue('G10', 'Grado :');
        $sheet->setCellValue('H10', strtoupper($this->section->grade?->name ?? ''));
        $sheet->setCellValue('I10', 'Sección :');
        $sheet->setCellValue('J10', strtoupper($this->section->name));

        // Combinaciones del modelo
        $merges = ['B4:J4', 'B5:D5', 'E5:F5', 'H5:J5', 'C6:J6', 'B7:J7', 'B8:C8', 'D8:J8', 'B9:C9', 'D9:J9', 'B10:C10', 'D10:F10'];
        foreach ($merges as $range) {
            $sheet->mergeCells($range);
        }

        $sheet->setCellValue('B12', 'ÁREAS');
        $row = 14;
        foreach ($this->areas as $i => $area) {
            [$code, $short] = $this->areaCode($area->name, $i);
            $sheet->setCellValue('B'.$row, "{$code}: {$short}");
            $sheet->setCellValue('C'.$row, mb_strtoupper($area->name));
            $sheet->mergeCells("C{$row}:J{$row}");
            $row++;
        }

        $this->styleLabels($sheet, ['B2', 'B4', 'B5', 'B6', 'B7', 'B8', 'B9', 'B10', 'G5', 'G10', 'I10', 'B12']);
        $this->borders($sheet, 'B4:J10');
        $this->borders($sheet, 'B12:J'.($row - 1));

        foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(14);
        }
        $sheet->getColumnDimension('C')->setWidth(45);
    }

    // ------------------------------------------------------------------
    // Hoja Parametros
    // ------------------------------------------------------------------
    private function buildParametros(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Parametros');

        $levelCode = self::SIAGIE_LEVEL_CODES[strtolower(trim((string) ($this->section->grade?->level?->code ?? 'primaria')))] ?? 'B0';
        $gradeCode = $this->gradeCode();

        $rows = [
            ['Código Modular:', $this->school->modular_code ?? '', $this->school->name],
            ['Anexo:', '0', ''],
            ['Nivel:', $levelCode, ucfirst($this->section->grade?->level?->name ?? 'Primaria')],
            ['Año Académico :', $this->yearNumber, ''],
            ['Diseño Curricular :', '20', 'CURRÍCULO NACIONAL 2017'],
            ['Periodo :', 'B'.$this->bimester, ['1' => 'PRIMER BIMESTRE', '2' => 'SEGUNDO BIMESTRE', '3' => 'TERCER BIMESTRE', '4' => 'CUARTO BIMESTRE'][$this->bimester]],
            ['Grado :', $gradeCode, strtoupper($this->section->grade?->name ?? '')],
            ['Sección :', '01', strtoupper($this->section->name)],
            ['Período promocional:', '', ''],
            ['Período promocional Equivalencia:', '', ''],
            ['Tipo de evaluación (1: Regular, 2:Postergacion, 3:Rectificacion)', '1', ''],
            ['Tipo de registro nota (0:Registro por Periodo, 1:Registro por nota final)', '0', ''],
        ];

        foreach ($rows as $i => [$a, $b, $c]) {
            $sheet->setCellValue('A'.($i + 1), $a);
            $sheet->setCellValue('B'.($i + 1), $b);
            $sheet->setCellValue('C'.($i + 1), $c);
        }

        $sheet->getColumnDimension('A')->setWidth(65);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(40);
    }

    // ------------------------------------------------------------------
    // Hoja por area
    // ------------------------------------------------------------------
    private function buildAreaSheet(Spreadsheet $spreadsheet, object $area): void
    {
        $competencies = $this->competencyByArea[$area->name] ?? collect();
        if ($competencies->isEmpty()) {
            return;
        }

        [$code, $short] = $this->areaCode($area->name, 0);
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(substr("{$code}-{$short}", 0, 31));

        // Encabezado doble: ID | Cód. Estudiante | Nombres | [NL, Conclusion] x competencia
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Cód. Estudiante');
        $sheet->setCellValue('C1', 'Nombres');

        $col = 4; // D
        foreach ($competencies as $competency) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $colLetter2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue($colLetter.'1', $competency->code);
            $sheet->setCellValue($colLetter.'2', 'NL');
            $sheet->setCellValue($colLetter2.'2', 'Conclusión descriptiva de la competencia');
            $sheet->mergeCells("{$colLetter}1:{$colLetter2}1");
            $sheet->getColumnDimension($colLetter)->setWidth(6);
            $sheet->getColumnDimension($colLetter2)->setWidth(50);
            $col += 2;
        }

        $sheet->mergeCells('A1:A2');
        $sheet->mergeCells('B1:B2');
        $sheet->mergeCells('C1:C2');

        // Alumnos
        $row = 3;
        foreach ($this->students as $student) {
            $person = $student->student?->person;
            $sheet->setCellValue('A'.$row, $person?->number ?? '');
            $sheet->setCellValue('B'.$row, $student->student?->student_code ?? '');
            $sheet->setCellValue('C'.$row, $person?->full_name ?? 'N/D');
            $sheet->getCell('C'.$row)->getStyle()->getAlignment()->setWrapText(false);

            $col = 4;
            foreach ($competencies as $competency) {
                $record = $this->records->get($student->id.'|'.$competency->id);
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $colLetter2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);

                if ($record) {
                    $record = $record->first();
                    $nl = $this->scale === AcaSchoolGradeCompetency::SCALE_LITERAL
                        ? ($record->score_letter ?? '')
                        : (string) ($record->score_number ?? '');
                    $sheet->setCellValue($colLetter.$row, $nl);
                    if (! empty($record->conclusion)) {
                        $sheet->setCellValue($colLetter2.$row, $record->conclusion);
                        $sheet->getStyle($colLetter2.$row)->getAlignment()->setWrapText(true);
                    }
                }
                $col += 2;
            }

            $row++;
        }

        // LEYENDA al pie (igual que el modelo SIAGIE)
        $row += 2;
        $sheet->setCellValue('B'.$row, 'LEYENDA');
        $row++;
        $sheet->setCellValue('B'.$row, 'NL = Nivel de logro alcanzado');
        foreach ($competencies as $competency) {
            $row++;
            $sheet->setCellValue('B'.$row, "{$competency->code} = {$competency->name}");
            $sheet->mergeCells("B{$row}:C{$row}");
        }

        // Estilos
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col - 1);
        $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $this->borders($sheet, "A3:{$lastCol}".($row - 1));
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(45);
        $sheet->getStyle('C3:C'.($row - 1))->getAlignment()->setWrapText(true);
    }

    // ------------------------------------------------------------------
    // Ayudantes
    // ------------------------------------------------------------------
    /** Codigo y abreviatura SIAGIE del area (con fallback por indice). */
    private function areaCode(string $areaName, int $index): array
    {
        $key = mb_strtolower(trim($areaName));

        return self::SIAGIE_AREA_CODES[$key] ?? [str_pad((string) (1000 + $index), 4, '0', STR_PAD_LEFT), strtoupper(mb_substr(preg_replace('/[^A-Za-z ]/', '', $areaName), 0, 9))];
    }

    /**
     * Codigo SIAGIE del grado: continuo en la EBR (Inicial 01-03,
     * Primaria 04-09, Secundaria 10-14; ej. 09 = SEXTO de Primaria).
     */
    private function gradeCode(): string
    {
        $levelCode = strtolower(trim((string) ($this->section->grade?->level?->code ?? 'primaria')));
        $name = $this->section->grade?->name ?? '';

        $words = ['PRIMERO' => 1, 'SEGUNDO' => 2, 'TERCERO' => 3, 'CUARTO' => 4, 'QUINTO' => 5, 'SEXTO' => 6, 'SEPTIMO' => 7];
        $upper = mb_strtoupper(trim($name));
        $num = null;

        if (preg_match('/(\d+)/', $upper, $m)) {
            $num = (int) $m[1];
        } elseif (preg_match('/(\d+)\s*A[ÑN]OS/', $upper, $m)) {
            $num = (int) $m[1];
        } else {
            foreach ($words as $word => $value) {
                if (str_contains($upper, $word)) {
                    $num = $value;
                    break;
                }
            }
        }

        if ($num === null) {
            return '00';
        }

        $offset = match ($levelCode) {
            'inicial' => 0,
            'primaria' => 3,
            'secundaria' => 9,
            default => 3,
        };

        return str_pad((string) ($offset + $num), 2, '0', STR_PAD_LEFT);
    }

    private function styleLabels(Worksheet $sheet, array $cells): void
    {
        foreach ($cells as $cell) {
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }
    }

    private function borders(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
    }
}
