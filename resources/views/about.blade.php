@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-8 lg:items-stretch lg:flex-row xl:gap-10">
    <div class="min-w-0 lg:flex lg:w-[56%] lg:flex-col xl:w-3/5">
        <section class="lg:flex-1">
        <p class="text-sm font-black uppercase tracking-widest">How Nodenex works</p>
        <h1 class="mt-2 text-4xl font-black uppercase sm:text-5xl">About Nodenex</h1>
        <p class="mt-4 max-w-xl text-base font-bold leading-7 sm:text-lg">Nodenex helps you discover movies by looking at the people and viewing choices that connect them.</p>

        <div class="mt-6 grid gap-4">
            <article class="border-4 border-black bg-white p-5 shadow-[5px_5px_0_#171717] sm:p-6">
                <p class="text-sm font-black uppercase">The short version</p>
                <p class="mt-2 text-base font-bold leading-7 sm:text-lg">When two movies share an actor, director, or audience, that connection can help us find something you may enjoy next.</p>
            </article>

            <article class="border-4 border-black bg-[#8ee7ff] p-5 shadow-[5px_5px_0_#171717] sm:p-6">
                <h2 class="text-xl font-black uppercase sm:text-2xl">What is connected?</h2>
                <p class="mt-2 text-base font-bold leading-7 sm:text-lg">The collection includes movies, actors, directors, and viewers. Each one is linked to the others through things they did or watched, so recommendations can follow those links.</p>
            </article>

            <article class="border-4 border-black bg-[#ffdf3f] p-5 shadow-[5px_5px_0_#171717] sm:p-6">
                <h2 class="text-xl font-black uppercase sm:text-2xl">Why use this approach?</h2>
                <p class="mt-2 text-base font-bold leading-7 sm:text-lg">A relationship-focused database makes it natural to ask questions such as “Which movies share people with this one?” or “Which viewers watched many of the same movies?”</p>
            </article>

            <article class="border-4 border-black bg-[#ff9f43] p-5 shadow-[5px_5px_0_#171717] sm:p-6">
                <h2 class="text-xl font-black uppercase sm:text-2xl">What powers it?</h2>
                <p class="mt-2 text-base font-bold leading-7 sm:text-lg">Laravel handles the website, while CognoDB stores the connected movie information. Bolt is the secure connection used to ask CognoDB for recommendations.</p>
            </article>

            <article class="border-4 border-black bg-[#ffb7d1] p-5 shadow-[5px_5px_0_#171717] sm:p-6">
                <h2 class="text-xl font-black uppercase sm:text-2xl">A note about SQL</h2>
                <p class="mt-2 text-base font-bold leading-7 sm:text-lg">Traditional SQL databases are excellent for many jobs. Nodenex uses a connection-focused database here because following several layers of movie and viewer relationships stays easier to understand as the search grows.</p>
            </article>
        </div>
        </section>
    </div>

    <div class="min-w-0 lg:flex lg:w-[44%] lg:flex-col xl:w-2/5">
        <aside class="lg:sticky lg:top-8 lg:h-full">
        <div class="flex h-full flex-col border-4 border-black bg-[#305a34] p-5 text-[#f7f1df] shadow-[5px_5px_0_#000000] sm:p-6">
            <p class="text-sm font-black uppercase tracking-widest text-[#ffdf3f]">Plain-language guide</p>
            <h2 class="mt-2 text-2xl font-black uppercase sm:text-3xl">Dictionary</h2>
            <p class="mt-2 text-sm font-bold leading-6 text-[#f7f1df]/80 sm:text-base">Here is what the labels and behind-the-scenes terms mean.</p>

            <dl class="mt-6 grid gap-x-6 xl:grid-cols-2">
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Connected through the cast</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">The movies share one or more actors.</dd>
                </div>
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Shared cast member</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">The person who appears in both movies and helps explain the recommendation.</dd>
                </div>
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Shared director</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">Both movies were directed by the same person.</dd>
                </div>
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Match strength</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">
                        <p>A comparison number showing how strongly the movie is connected. A higher number means more useful connections were found.</p>
                        <div class="mt-3 grid gap-2 text-xs font-black uppercase">
                            <div class="flex items-center gap-2"><span class="w-14 shrink-0 text-[#f7f1df]/70">0–0.9</span><span class="h-2 flex-1 bg-[#8ee7ff]"></span><span>Light</span></div>
                            <div class="flex items-center gap-2"><span class="w-14 shrink-0 text-[#f7f1df]/70">1–1.9</span><span class="h-2 flex-1 bg-[#ffdf3f]"></span><span>Good</span></div>
                            <div class="flex items-center gap-2"><span class="w-14 shrink-0 text-[#f7f1df]/70">2+</span><span class="h-2 flex-1 bg-[#ff5c8a]"></span><span>Strong</span></div>
                        </div>
                        <p class="mt-2 text-xs font-bold normal-case text-[#f7f1df]/70">These are guide values, not a score out of 10. Compare results within the same recommendation list.</p>
                    </dd>
                </div>
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Connections away</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">How many relationship steps separate the recommendation from the movie or viewer you started with.</dd>
                </div>
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Recommended by viewers with similar taste</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">The number of other viewers whose watched movies helped surface this recommendation.</dd>
                </div>
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Watched by users</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">How many viewers in the collection have watched that movie.</dd>
                </div>
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Movie, actor, director, and viewer</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">The four kinds of things stored in the collection.</dd>
                </div>
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Node</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">A saved item in the collection, such as a movie or a person.</dd>
                </div>
                <div class="border-b-2 border-[#f7f1df]/20 py-3 xl:border-b-2">
                    <dt class="font-black text-[#ffdf3f]">Relationship</dt>
                    <dd class="mt-1 text-sm font-bold leading-6">A link between two saved items, such as “acted in,” “directed,” or “watched.”</dd>
                </div>
            </dl>
        </div>
        </aside>
    </div>
</div>
@endsection
