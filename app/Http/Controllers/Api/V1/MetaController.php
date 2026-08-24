<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Static reference data the onboarding wizard and donor-profile edit screen
 * both need to populate their pickers — same source config as the web
 * onboarding/profile views (config/juniv.php), not a second copy of it.
 */
class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'halls' => config('juniv.halls'),
            'departments' => config('juniv.departments'),
            'blood_groups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'batches' => User::batchOptions(),
        ]);
    }
}
