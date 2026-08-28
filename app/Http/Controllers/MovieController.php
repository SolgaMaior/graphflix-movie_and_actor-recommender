<?php

namespace App\Http\Controllers;

use App\Services\GraphService;

final class MovieController extends Controller
{
    public function show(string $title, GraphService $graph)
    {
        $actors = $graph->actorsForMovie($title);
        $directors = $graph->directorsForMovie($title);
        $movie = $graph->movieByTitle($title);
        $becauseYouWatched = $graph->recommendationsForMovie($title);
        $similarFromOtherActors = $graph->otherMoviesByActors($title);

        return view('movies.show', compact('title', 'movie', 'actors', 'directors', 'becauseYouWatched', 'similarFromOtherActors') + [
            'error' => $graph->error(),
        ]);
    }
}
