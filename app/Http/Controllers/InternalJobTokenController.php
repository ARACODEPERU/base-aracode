<?php

namespace App\Http\Controllers;

use App\Services\InternalJobTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InternalJobTokenController extends Controller
{
    public function store(InternalJobTokenService $jobTokenService): JsonResponse
    {
        $userId = Auth::id();

        if ($userId === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'token' => $jobTokenService->issue((int) $userId),
        ]);
    }
}
