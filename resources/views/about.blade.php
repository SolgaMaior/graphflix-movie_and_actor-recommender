@extends('layouts.app')
@section('content')
<h1 class="text-4xl font-black uppercase">About Graphflix</h1>
<div class="mt-6 max-w-3xl space-y-4 text-lg font-bold">
    <p class="border-4 border-black bg-white p-5 shadow-[6px_6px_0_#171717]">Graphflix uses Laravel for HTTP requests and CognoDB for graph recommendations over Bolt.</p>
    <p class="border-4 border-black bg-[#8ee7ff] p-5 shadow-[6px_6px_0_#171717]">The graph contains Movie, Actor, Director, and User nodes connected by ACTED_IN, DIRECTED, and WATCHED relationships.</p>
    <p class="border-4 border-black bg-[#ffdf3f] p-5 shadow-[6px_6px_0_#171717]">A graph database is useful here because recommendations depend on relationships: which people connect two movies, how many hops apart they are, and how many alternative paths exist.</p>
    <p class="border-4 border-black bg-[#ff9f43] p-5 shadow-[6px_6px_0_#171717]">SQL can keep up closely for simple lookups and fixed-depth joins. The difference is complexity: as the question expands from two hops to four or six, SQL needs repeated self-joins, many table aliases, or a recursive CTE. The query can become difficult to maintain, and the application may need to reconstruct paths and distances after the database returns rows.</p>
    <p class="border-4 border-black bg-[#ffb7d1] p-5 shadow-[6px_6px_0_#171717]">CognoDB keeps the connections as first-class relationships. A variable-length traversal can follow the existing edges, calculate distance and path counts, and return the connecting actor or director directly. This does not mean SQL is slow or incapable; it means the graph model keeps relationship-heavy recommendation logic more direct as the number of hops grows.</p>
</div>
@endsection
