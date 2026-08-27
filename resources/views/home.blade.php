@extends('layouts.app')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div><p class="text-sm font-black uppercase">Find Movies</p><h1 class="text-4xl font-black uppercase">Browse movies</h1></div>
    <form class="flex gap-2" method="get">
        <input name="genre" value="{{ $genre ?? '' }}" placeholder="Filter genre" class="border-4 border-black bg-white px-4 py-2 text-sm font-bold shadow-[4px_4px_0_#171717]">
        <button class="border-4 border-black bg-[#ff5c8a] px-4 py-2 text-sm font-black uppercase shadow-[4px_4px_0_#171717]">Filter</button>
    </form>
</div>
@if (count($movies))
<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
@foreach ($movies as $movie)
    <a href="{{ url('/movies/'.rawurlencode($movie->title)) }}" class="border-4 border-black bg-white p-5 shadow-[6px_6px_0_#171717] hover:translate-x-1 hover:translate-y-1 hover:shadow-none">
        <h2 class="font-black">{{ $movie->title }}</h2><p class="mt-2 text-sm font-bold">{{ $movie->genre ?? 'Unknown' }} · {{ $movie->year ?? '—' }}</p>
    </a>
@endforeach
</div>
@else
<p class="mt-8 rounded-lg border border-slate-800 p-6 text-slate-400">No movies found.</p>
@endif
@endsection
