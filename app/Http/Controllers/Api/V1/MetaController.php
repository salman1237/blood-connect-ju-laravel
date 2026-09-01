<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Static reference data the onboarding wizard and donor-profile edit screen
 * both need to populate their pickers — same source config as the web
 * onboarding/profile views (config/juniv.php), not a second copy of it. Also
 * carries the admin-editable org credit (web's partials/org-credit.blade.php
 * equivalent) so Android's Settings screen can show the same "Implemented &
 * funded by...", "Maintained by..." lines without a separate endpoint.
 */
class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $setting = AppSetting::current();

        return response()->json([
            'halls' => config('juniv.halls'),
            'departments' => config('juniv.departments'),
            'blood_groups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'batches' => User::batchOptions(),
            'org' => [
                'funded_by' => $setting->funded_by,
                'maintained_by' => $setting->maintained_by,
                'logo_url' => $setting->logo_url,
            ],
        ]);
    }
}
