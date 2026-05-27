<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Socialevents\Services\SocialeventsDashboardService;

class SocialeventsController extends Controller
{
    public function __construct(
        private SocialeventsDashboardService $dashboardService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Socialevents::Dashboard', $this->dashboardService->build());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('socialevents::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('socialevents::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('socialevents::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
