<?php

namespace App\Http\Controllers;

use App\Services\GraphService;
use Illuminate\Http\JsonResponse;

final class MovieController extends Controller
{
    public function show(string $title, GraphService $graph): JsonResponse
    {
        return response()->json([
            'movie' => $title,
            'becauseYouWatched' => $graph->recommendationsForMovie($title, 2, 2),
            'similarFromOtherActors' => $graph->recommendationsForMovie($title, 3, 6),
        ]);
    }
}
