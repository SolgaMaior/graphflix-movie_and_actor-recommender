@extends('layouts.app')
@section('content')
<section class="border-4 border-black bg-[#8ee7ff] p-8 shadow-[10px_10px_0_#171717] md:p-14">
    <p class="text-sm font-black uppercase tracking-widest">Movie recommendations with graphs</p>
    <h1 class="mt-4 max-w-3xl text-5xl font-black uppercase leading-none md:text-7xl">Find your next connection.</h1>
    <p class="mt-6 max-w-xl text-lg font-bold">Explore movies, actors, directors, and user taste connected in CognoDB.</p>
    <a href="{{ url('/home') }}" class="mt-8 inline-block border-4 border-black bg-[#ffdf3f] px-5 py-3 font-black uppercase shadow-[5px_5px_0_#171717] hover:translate-x-1 hover:translate-y-1 hover:shadow-none">Browse movies →</a>
</section>
@endsection
