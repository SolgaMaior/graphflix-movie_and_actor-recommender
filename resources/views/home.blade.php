@extends('layouts.app')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><p class="text-sm text-emerald-400">500 seeded movies</p><h1 class="text-3xl font-bold">Browse movies</h1></div>
    <form class="flex gap-2" method="get">
        <input name="genre" value="{{ $genre ?? '' }}" placeholder="Filter genre" class="rounded-lg border border-slate-700 bg-slate-900 px-4 py-2 text-sm">
        <button class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-medium text-slate-950">Filter</button>
    </form>
</div>
@if (count($movies))
<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
@foreach ($movies as $movie)
    <a href="{{ url('/movies/'.rawurlencode($movie->title)) }}" class="rounded-xl border border-slate-800 bg-slate-900 p-5 hover:border-emerald-500">
        <h2 class="font-semibold">{{ $movie->title }}</h2><p class="mt-2 text-sm text-slate-400">{{ $movie->genre ?? 'Unknown' }} · {{ $movie->year ?? '—' }}</p>
    </a>
@endforeach
</div>
@else
<p class="mt-8 rounded-lg border border-slate-800 p-6 text-slate-400">No movies found.</p>
@endif
@endsection
