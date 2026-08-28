@extends('layouts.app')
@section('content')
<p class="text-sm font-black uppercase">Chosen by the users</p><h1 class="mt-1 text-4xl font-black uppercase">Popular movies</h1>
<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @forelse ($movies as $movie)
        <a href="{{ url('/movies/'.rawurlencode($movie->title)) }}" class="border-4 border-black bg-[#ff9f43] p-5 shadow-[6px_6px_0_#171717] hover:translate-x-1 hover:translate-y-1 hover:shadow-none">
            <p class="font-black">{{ $movie->title }}</p>
            <p class="mt-2 text-sm font-bold">{{ $movie->genre ?? 'Unknown' }} · {{ $movie->year ?? '—' }}</p>
            <p class="mt-2 text-xs font-black uppercase">Watched by {{ $movie->watchers }} users</p>
        </a>
    @empty
        <p class="font-bold">No popular movies found.</p>
    @endforelse
</div>

<section class="mt-12">
    <h2 class="text-2xl font-black uppercase">Movie fans</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($users as $user)
        <a href="{{ url('/users/'.rawurlencode($user->id)) }}" class="border-4 border-black bg-[#8ee7ff] p-5 shadow-[6px_6px_0_#171717] hover:translate-x-1 hover:translate-y-1 hover:shadow-none">
            <p class="font-black">{{ $user->name }}</p><p class="mt-2 text-sm font-bold">See recommendations based on this viewer’s taste</p>
        </a>
        
        @empty
        <p class="font-bold">No viewers found.</p>
        @endforelse</div></section>
@endsection
