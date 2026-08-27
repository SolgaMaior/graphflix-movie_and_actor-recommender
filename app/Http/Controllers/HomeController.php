<?php

namespace App\Http\Controllers;

use App\Services\GraphService;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function index(Request $request, GraphService $graph)
    {
        $genre = $request->string('genre')->toString() ?: null;

        return view('home', [
            'genre' => $genre,
            'movies' => $graph->moviesByGenre($genre),
            'error' => $graph->error(),
        ]);
    }
}
