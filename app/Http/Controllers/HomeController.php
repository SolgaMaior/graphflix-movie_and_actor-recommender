<?php

namespace App\Http\Controllers;

use App\Services\GraphService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function index(Request $request, GraphService $graph): JsonResponse
    {
        return response()->json([
            'genre' => $request->string('genre')->toString() ?: null,
            'movies' => $graph->moviesByGenre($request->string('genre')->toString() ?: null),
        ]);
    }
}
