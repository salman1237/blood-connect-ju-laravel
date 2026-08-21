<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MyRequestsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('requests.mine', [
            'myRequests' => $request->user()->bloodRequests()->with('requester')->latest()->get(),
        ]);
    }
}
