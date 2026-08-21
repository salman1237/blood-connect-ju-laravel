<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DonationHistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('donations.index', [
            'donationHistory' => $request->user()->donationHistory()->with('bloodRequest')->latest('confirmed_at')->get(),
            'badges' => $request->user()->badges,
        ]);
    }
}
