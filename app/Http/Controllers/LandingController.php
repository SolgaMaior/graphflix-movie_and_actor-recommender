<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class LandingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Welcome to Graphflix.']);
    }
}
