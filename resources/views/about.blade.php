@extends('layouts.app')
@section('content')
<h1 class="text-3xl font-bold">About Graphflix</h1><div class="mt-6 max-w-2xl space-y-4 text-slate-400"><p>Graphflix uses Laravel for HTTP requests and CognoDB for graph recommendations over Bolt.</p><p>The graph contains Movie, Actor, Director, and User nodes connected by ACTED_IN, DIRECTED, and WATCHED relationships.</p><p>Recommendations use variable-length graph traversals to find useful connections across the seeded dataset.</p></div>
@endsection
