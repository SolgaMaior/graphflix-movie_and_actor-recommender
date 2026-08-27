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
        // Explore deeper actor/director paths for network-based recommendations.
        $becauseYouWatched = $graph->recommendationsForMovie($title, 3, 6);
        $similarFromOtherActors = $graph->otherMoviesByActors($title);

        return view('movies.show', compact('title', 'movie', 'actors', 'directors', 'becauseYouWatched', 'similarFromOtherActors') + [
            'error' => $graph->error(),
        ]);
    }
}
