<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class AboutController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'name' => 'Graphflix',
            'architecture' => 'Laravel HTTP layer backed by a CognoDB graph database over Bolt.',
            'graph' => ['labels' => ['Movie', 'Actor', 'Director', 'User'], 'relationships' => ['ACTED_IN', 'DIRECTED', 'WATCHED']],
        ]);
    }
}
