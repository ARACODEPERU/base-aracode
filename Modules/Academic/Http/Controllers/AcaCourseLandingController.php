<?php

namespace Modules\Academic\Http\Controllers;

use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCourseLanding;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Onlineshop\Entities\OnliItem;

class AcaCourseLandingController extends Controller
{
    use ValidatesRequests;

    public function edit($courseId)
    {
        $course = AcaCourse::with('category', 'modality')->findOrFail($courseId);

        $landing = AcaCourseLanding::where('course_id', $courseId)->first();

        if (! $landing) {
            $landing = AcaCourseLanding::create([
                'course_id' => $courseId,
                'url_slug' => AcaCourseLanding::generateSlug($course->description),
                'banner_language' => 'es',
                'is_published' => false,
            ]);
        }

        $teachers = AcaTeacher::with('person')->get()->map(function ($teacher) {
            return [
                'id' => $teacher->id,
                'person_id' => $teacher->person_id,
                'person' => $teacher->person ? [
                    'id' => $teacher->person->id,
                    'name' => $teacher->person->name,
                    'formatted_name' => $teacher->person->formatted_name,
                    'image' => $teacher->person->image,
                    'ocupacion' => $teacher->person->ocupacion,
                ] : null,
            ];
        });

        $people = Person::select('id', 'names', 'full_name', 'image', 'ocupacion')
            ->orderBy('full_name')
            ->get()
            ->map(function ($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->names,
                    'full_name' => $person->full_name,
                    'formatted_name' => $person->formatted_name ?? $person->full_name,
                    'image' => $person->image,
                    'ocupacion' => $person->ocupacion,
                ];
            });

        return Inertia::render('Academic::Courses/Landing', [
            'course' => $course,
            'landing' => $landing,
            'languageOptions' => AcaCourseLanding::getLanguageOptions(),
            'teachers' => $teachers,
            'people' => $people,
        ]);
    }

    public function updateGeneral(Request $request, $courseId)
    {
        $this->validate(
            $request,
            [
                'url_slug' => 'required|string|max:500|unique:aca_course_landings,url_slug,'.$courseId.',course_id',
                'whatsapp_link' => 'nullable|string|max:500',
                'is_published' => 'boolean',
            ]
        );

        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();

        // Limpiar url_slug: eliminar espacios y caracteres especiales
        $slug = trim($request->url_slug);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        $landing->update([
            'url_slug' => $slug,
            'whatsapp_link' => $request->whatsapp_link ?? '',
            'is_published' => $request->is_published ?? false,
        ]);
    }

    public function updateBanner(Request $request, $courseId)
    {
        $this->validate(
            $request,
            [
                'banner_start_date' => 'required|date',
                'banner_end_date' => 'required|date|after_or_equal:banner_start_date',
                'banner_language' => 'required|in:es,en,zh',
            ]
        );

        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();

        $startDate = Carbon::parse($request->banner_start_date);
        $endDate = Carbon::parse($request->banner_end_date);
        $duration = $startDate->diffInDays($endDate) + 1;

        $landing->update([
            'banner_start_date' => $request->banner_start_date,
            'banner_end_date' => $request->banner_end_date,
            'banner_duration' => $duration,
            'banner_language' => $request->banner_language,
        ]);
    }

    public function updateProfessional(Request $request, $courseId)
    {
        $this->validate(
            $request,
            [
                'name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'items' => 'nullable|array',
                'items.*.icon' => 'nullable|string|max:100',
                'items.*.title' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
            ]
        );

        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();

        $professionalUpdate = [
            'name' => $request->name ?? '',
            'title' => $request->title ?? '',
            'description' => $request->description ?? '',
            'items' => $request->items ?? [
                ['icon' => '', 'title' => '', 'description' => ''],
                ['icon' => '', 'title' => '', 'description' => ''],
            ],
        ];

        $landing->update([
            'professional_section' => $professionalUpdate,
        ]);
    }

    public function updateStaff(Request $request, $courseId)
    {
        $this->validate(
            $request,
            [
                'name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:300',
                'description' => 'nullable|string',
                'teachers' => 'nullable|array',
                'teachers.*.teacher_id' => 'required|integer',
                'teachers.*.selected' => 'nullable|boolean',
            ]
        );

        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();

        $staffUpdate = [
            'name' => $request->name ?? '',
            'title' => $request->title ?? '',
            'description' => $request->description ?? '',
            'teachers' => $request->teachers ?? [],
        ];

        $landing->update([
            'staff_section' => $staffUpdate,
        ]);
    }

    public function updateResults(Request $request, $courseId)
    {
        $this->validate(
            $request,
            [
                'name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:300',
                'description' => 'nullable|string',
                'items' => 'nullable|array',
                'items.*.icon' => 'nullable|string|max:100',
                'items.*.title' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
            ]
        );

        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();

        $resultsUpdate = [
            'name' => $request->name ?? '',
            'title' => $request->title ?? '',
            'description' => $request->description ?? '',
            'items' => $request->items ?? [
                ['icon' => '', 'title' => '', 'description' => ''],
                ['icon' => '', 'title' => '', 'description' => ''],
            ],
        ];

        $landing->update([
            'results_section' => $resultsUpdate,
        ]);
    }

    public function updateTestimonials(Request $request)
    {

        $this->validate(
            $request,
            [
                'name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:300',
                'description' => 'nullable|string',
                'items' => 'nullable|array',
                'items.*.name' => 'required|string|max:255',
                'items.*.presentation' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
            ]
        );

        $courseId = $request->course_id;

        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();

        $items = $request->items ?? [];

        foreach ($items as &$item) {
            if (isset($item['image']) && $item['image'] instanceof \Illuminate\Http\UploadedFile) {
                $destination = 'uploads/landing/testimonials/' . $courseId;
                $originalName = strtolower(trim($item['image']->getClientOriginalName()));
                $originalName = str_replace(" ", "_", $originalName);
                $extension = $item['image']->getClientOriginalExtension();
                $fileName = $originalName . '.' . $extension;
                $path = $item['image']->storeAs($destination, $fileName, 'public');
                $item['image'] = $path;
            }
        }

        $testimonialsUpdate = [
            'name' => $request->name ?? '',
            'title' => $request->title ?? '',
            'description' => $request->description ?? '',
            'items' => $items,
        ];

        $landing->update([
            'testimonials_section' => $testimonialsUpdate,
        ]);
    }

    public function updateStudyPlan(Request $request)
    {

        $this->validate(
            $request,
            [
                'name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:300',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'items' => 'nullable|array',
                'items.*.number' => 'required|integer',
                'items.*.title' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
            ]
        );

        $courseId = $request->course_id;

        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();

        $path = null;
        $destination = 'uploads/landing/study_plan';
        $file = $request->file('image');
        if ($file) {
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $original_name = str_replace(" ", "_", $original_name);
            $extension = $file->getClientOriginalExtension();
            $file_name = $original_name . '.' . $extension;
            $path = $request->file('image')->storeAs(
                $destination,
                $file_name,
                'public'
            );

        }


        $studyPlanUpdate = [
            'name' => $request->name ?? '',
            'title' => $request->title ?? '',
            'description' => $request->description ?? '',
            'image' => $path,
            'items' => $request->items ?? [],
        ];

        $landing->update([
            'study_plan_section' => $studyPlanUpdate,
        ]);
    }

    public function updateProblem(Request $request, $courseId)
    {
        $this->validate(
            $request,
            [
                'name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:300',
                'description' => 'nullable|string',
                'items' => 'nullable|array',
                'items.*.icon' => 'nullable|string|max:100',
                'items.*.title' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
            ]
        );

        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();

        $defaultItems = [
            ['icon' => '', 'title' => '', 'description' => ''],
            ['icon' => '', 'title' => '', 'description' => ''],
            ['icon' => '', 'title' => '', 'description' => ''],
        ];

        $problemUpdate = [
            'name' => $request->name ?? '',
            'title' => $request->title ?? '',
            'description' => $request->description ?? '',
            'items' => $request->items ?? $defaultItems,
        ];

        $landing->update([
            'problem_section' => $problemUpdate,
        ]);
    }

    public function updateInvestment(Request $request, $courseId)
    {

        $this->validate(
            $request,
            [
                'name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:300',
                'description' => 'nullable|string',
                'items' => 'nullable|array',
                'items.*.tag' => 'nullable|string|max:100',
                'items.*.title' => 'nullable|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.price_before' => 'nullable|max:50',
                'items.*.price_before_text' => 'nullable|string|max:50',
                'items.*.price_before_visible' => 'nullable|boolean',
                'items.*.price_now' => 'nullable|max:50',
                'items.*.price_now_text' => 'nullable|string|max:50',
                'items.*.features' => 'nullable|array',
            ]
        );
        //dd($request->all());
        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();
        //modificando el precio en onliItem para que se pueda cobrar bien en el carrito
        $onliItem = OnliItem::where('item_id', $courseId)->first();
        $onliItem->update([
            'price' => $request->input('items.0.price_now'),
        ]);

        $defaultItems = [
            [
                'tag' => '',
                'title' => '',
                'description' => '',
                'price_before' => '',
                'price_before_text' => '',
                'price_before_visible' => false,
                'price_now' => '',
                'price_now_text' => '',
                'features' => [],
            ],
            [
                'tag' => '',
                'title' => '',
                'description' => '',
                'price_before' => '',
                'price_before_text' => '',
                'price_before_visible' => false,
                'price_now' => '',
                'price_now_text' => '',
                'features' => [],
            ],
        ];

        $investmentUpdate = [
            'name' => $request->name ?? '',
            'title' => $request->title ?? '',
            'description' => $request->description ?? '',
            'items' => $request->items ?? $defaultItems,
        ];

        $landing->update([
            'investment_section' => $investmentUpdate,
        ]);
    }

    public function updateFaq(Request $request, $courseId)
    {
        $this->validate(
            $request,
            [
                'name' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:300',
                'description' => 'nullable|string',
                'items' => 'nullable|array',
                'items.*.question' => 'nullable|string|max:500',
                'items.*.answer' => 'nullable|string',
                'items.*.sort' => 'nullable|integer|min:0',
                'items.*.visible' => 'nullable|boolean',
            ]
        );

        $landing = AcaCourseLanding::where('course_id', $courseId)->firstOrFail();

        $defaultItems = [
            [
                'question' => '',
                'answer' => '',
                'sort' => 1,
                'visible' => true,
            ],
        ];

        $faqUpdate = [
            'name' => $request->name ?? 'Preguntas Frecuentes',
            'title' => $request->title ?? '',
            'description' => $request->description ?? '',
            'items' => $request->items ?? $defaultItems,
        ];

        $landing->update([
            'faq_section' => $faqUpdate,
        ]);
    }

    public function show($slug)
    {
        $landing = AcaCourseLanding::where('url_slug', $slug)
            ->where('is_published', true)
            ->with('course.category', 'course.modality')
            ->firstOrFail();

        return Inertia::render('Academic::Courses/PublicLanding', [
            'landing' => $landing,
        ]);
    }
}
