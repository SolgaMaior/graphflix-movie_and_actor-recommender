@extends('layouts.app')
@section('content')
<a href="{{ url('/users') }}" class="text-sm text-emerald-400">← Back to users</a><h1 class="mt-4 text-3xl font-bold">Recommendations for {{ $id }}</h1>
@foreach ([['Users with similar taste', $similarUsers], ['Movies people like you watched', $recommendedMovies]] as [$heading, $items])<section class="mt-10"><h2 class="text-xl font-semibold">{{ $heading }}</h2>@if(count($items))<div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach($items as $item)<div class="rounded-xl border border-slate-800 bg-slate-900 p-5"><h3 class="font-medium">{{ $item->name ?? $item->title }}</h3><p class="mt-2 text-sm text-slate-400">{{ $item->sharedMovies ? $item->sharedMovies.' shared movies' : 'Recommended through similar taste' }}</p></div>@endforeach</div>@else<p class="mt-4 text-slate-400">No recommendations found.</p>@endif</section>@endforeach
@endsection
