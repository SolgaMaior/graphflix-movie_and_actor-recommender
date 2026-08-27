@extends('layouts.app')
@section('content')
<a href="{{ url('/home') }}" class="text-sm text-emerald-400">← Back to movies</a>
<h1 class="mt-4 text-4xl font-black uppercase">Because you watched “{{ $title }}”</h1>
<div class="mt-4 border-4 border-black bg-[#8ee7ff] p-5 shadow-[6px_6px_0_#171717]">
    <p class="font-black uppercase">Genre: {{ $movie?->genre ?? 'Unknown' }}</p>
    <p class="mt-1 font-bold">Year: {{ $movie?->year ?? 'Unknown' }}</p>
    <p class="mt-1 font-bold">Actors: {{ implode(', ', $actors) ?: 'Unknown cast' }}</p>
    <p class="mt-1 font-bold">Director: {{ implode(', ', $directors) ?: 'Unknown' }}</p>
</div>
@foreach ([['Because you watched “'.$title.'” — deeper network recommendations', $becauseYouWatched, 'Shared cast or director connection'], ['Other movies by actors in “'.$title.'”', $similarFromOtherActors, 'Shared actor connection']] as [$heading, $items, $fallbackDescription])
<section class="mt-10"><h2 class="text-2xl font-black uppercase">{{ $heading }}</h2>
@if (count($items))<div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach ($items as $item)<a href="{{ url('/movies/'.rawurlencode($item->title)) }}" class="block border-4 border-black bg-[#ffdf3f] p-5 text-inherit shadow-[6px_6px_0_#171717] hover:translate-x-1 hover:translate-y-1 hover:shadow-none"><h3 class="font-black">{{ $item->title }}</h3><p class="mt-2 text-sm font-bold">@if($item->connectorName && $item->connectorType === 'Actor') Shared actor: {{ $item->connectorName }} @elseif($item->connectorName && $item->connectorType === 'Director') Shared director: {{ $item->connectorName }} @else {{ $fallbackDescription }} @endif</p><p class="mt-1 text-xs font-black uppercase">Distance: {{ $item->distance }} hops</p></a>@endforeach</div>
@else <p class="mt-4 text-slate-400">No connected recommendations yet.</p>@endif</section>
@endforeach
@endsection
