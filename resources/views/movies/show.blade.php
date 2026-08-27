@extends('layouts.app')
@section('content')
<a href="{{ url('/home') }}" class="text-sm text-emerald-400">← Back to movies</a>
<h1 class="mt-4 text-3xl font-bold">Because you watched “{{ $title }}”</h1>
@php($actor = $actors[0] ?? 'an unknown actor')
<p class="mt-2 text-slate-400">Starring {{ $actor }}</p>
@foreach ([['Because you watched “'.$title.'” starring “'.$actor.'” — deeper network recommendations', $becauseYouWatched], ['Other movies by '.$actor, $similarFromOtherActors]] as [$heading, $items])
<section class="mt-10"><h2 class="text-xl font-semibold">{{ $heading }}</h2>
@if (count($items))<div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach ($items as $item)<div class="rounded-xl border border-slate-800 bg-slate-900 p-5"><h3 class="font-medium">{{ $item->title }}</h3><p class="mt-2 text-sm text-slate-400">{{ $item->connectorName ? 'Starring '.$item->connectorName : 'Movie network connection' }}</p><p class="mt-1 text-xs text-slate-500">Distance: {{ $item->distance }} hops</p></div>@endforeach</div>
@else <p class="mt-4 text-slate-400">No connected recommendations yet.</p>@endif</section>
@endforeach
@endsection
