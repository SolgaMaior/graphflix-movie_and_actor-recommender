@extends('layouts.app')
@section('content')
<a href="{{ url('/users') }}" class="border-4 text-[15px] border-black bg-[#ffffff] p-1 shadow-[6px_6px_0_#171717] hover:translate-x-1 hover:translate-y-1 hover:shadow-none">← Back to popular movies</a>

<h1 class="mt-4 text-4xl font-black uppercase">Recommendations for {{ $id }}</h1>

@foreach ([['Users with similar taste', $similarUsers], ['Movies people like you watched', $recommendedMovies]] as [$heading, $items])
    <section class="mt-10">
        <h2 class="text-2xl font-black uppercase">{{ $heading }}</h2>

        @if(count($items))
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    
                @foreach($items as $item)
                    <div class="border-4 border-black bg-[#8ee7ff] p-5 shadow-[6px_6px_0_#171717]">
                        <h3 class="font-black">{{ $item->name ?? $item->title }}</h3>
        
                        @if($item->sharedMovies)
                            <p class="mt-2 text-sm font-bold">You both watched {{ $item->sharedMovies }} movie{{ $item->sharedMovies === 1 ? '' : 's' }}</p>
                        @elseif($item->pathCount)
                        <p class="mt-2 text-sm font-bold">Recommended by {{ $item->pathCount }} viewer{{ $item->pathCount === 1 ? '' : 's' }} with similar taste</p>
                        @else
                        <p class="mt-2 text-sm font-bold">A good match based on viewing history</p>
                        @endif
                        
                            @if($item->distance)
                            <p class="mt-1 text-xs font-bold">{{ $item->distance }} connections away</p>
                            @endif
                            <p class="mt-1 text-xs font-black uppercase">Match strength: {{ number_format($item->relevanceScore, 1) }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-4 font-bold">No recommendations found.</p>
        @endif
    </section>
@endforeach
@endsection
