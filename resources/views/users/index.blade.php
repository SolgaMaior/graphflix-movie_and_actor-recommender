@extends('layouts.app')
@section('content')
<p class="text-sm text-emerald-400">Chosen by the graph’s users</p><h1 class="mt-1 text-3xl font-bold">Popular movies</h1>
<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@forelse ($movies as $movie)<a href="{{ url('/movies/'.rawurlencode($movie->title)) }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-emerald-500"><p class="font-medium">{{ $movie->title }}</p><p class="mt-2 text-sm text-slate-400">{{ $movie->genre ?? 'Unknown' }} · {{ $movie->year ?? '—' }}</p><p class="mt-2 text-xs text-slate-500">Watched by {{ $movie->watchers }} users</p></a>@empty<p class="text-slate-400">No popular movies found.</p>@endforelse</div>
@endsection
