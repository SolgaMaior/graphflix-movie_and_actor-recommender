<?php

namespace App\Http\Controllers;

use App\Services\GraphService;

final class UserController extends Controller
{
    public function index(GraphService $graph)
    {
        return view('users.index', [
            'movies' => $graph->popularMoviesByUsers(),
            'users' => $graph->allUsers(),
            'error' => $graph->error(),
        ]);
    }

    public function show(string $id, GraphService $graph)
    {
        return view('users.show', [
            'id' => $id,
            'similarUsers' => $graph->similarUsers($id),
            'recommendedMovies' => $graph->recommendedMoviesForUser($id),
            'error' => $graph->error(),
        ]);
    }
}
