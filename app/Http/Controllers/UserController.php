<?php

namespace App\Http\Controllers;

use App\Services\GraphService;
use Illuminate\Http\JsonResponse;

final class UserController extends Controller
{
    public function index(GraphService $graph): JsonResponse
    {
        return response()->json(['users' => $graph->allUsers()]);
    }

    public function show(string $id, GraphService $graph): JsonResponse
    {
        return response()->json([
            'user' => $id,
            'similarUsers' => $graph->similarUsers($id),
            'recommendedMovies' => $graph->recommendedMoviesForUser($id),
        ]);
    }
}
