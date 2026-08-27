@extends('layouts.app')
@section('content')
<section class="rounded-2xl border border-slate-800 bg-slate-900 p-10">
    <p class="text-sm font-medium uppercase tracking-widest text-emerald-400">Movie recommendations with graphs</p>
    <h1 class="mt-4 text-4xl font-bold">Find your next connection.</h1>
    <p class="mt-4 max-w-xl text-slate-400">Explore movies, actors, directors, and user taste connected in CognoDB.</p>
    <a href="{{ url('/home') }}" class="mt-8 inline-block rounded-lg bg-emerald-500 px-5 py-3 font-medium text-slate-950">Browse movies</a>
</section>
@endsection
