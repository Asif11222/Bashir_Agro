<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AppAppearanceService;
use Illuminate\Http\JsonResponse;

class AppAppearanceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => AppAppearanceService::apiPayload()])
            ->header('Cache-Control', 'private, no-cache');
    }
}
