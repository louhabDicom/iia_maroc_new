{{--
    Who is backing this.

    With no sponsors on file — which is the state today — this renders the
    configured placeholder mark rather than an empty band. One designed logo
    reads as a wall of one; a dark rectangle with nothing in it reads as a page
    that failed to load. Publishing the first real sponsor replaces it with no
    change to the markup.

    Logos sit on white plates: partner marks are usually dark line art, and dark
    line art on a tinted section disappears.
--}}
@props(['sponsors'])

@php
    $fallback = (array) config('brand.homepage.partners_fallback');
@endphp

<section class="h-sponsors" aria-labelledby="sponsors-title">
    <div class="container">

        <h2 id="sponsors-title" class="h-sponsors__title">@lang('home.partners_title')</h2>

        @if ($sponsors->isNotEmpty())
            <ul class="h-sponsors__wall">
                @foreach ($sponsors as $sponsor)
                    <li class="h-sponsors__item">
                        <x-sponsor-logo :sponsor="$sponsor" />
                    </li>
                @endforeach
            </ul>

            <p class="h-sponsors__cta">
                <a href="{{ route('sponsors') }}" class="h-btn h-btn--outline h-btn--sm">
                    @lang('sponsoring.become_partner')
                </a>
            </p>
@else
    <ul class="h-sponsors__wall h-sponsors__wall--static">
        @foreach ($fallback as $partner)
            <li class="h-sponsors__item">
                <img src="{{ asset($partner['src']) }}"
                     alt="{{ $partner['name'] }}"
                     loading="lazy"
                     decoding="async">
            </li>
        @endforeach
    </ul>
@endif

    </div>
</section>
<style>
    .h-sponsors__wall--static {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.h-sponsors__wall--static .h-sponsors__item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 180px;
    height: 100px;
    padding: 12px 16px;
    background: #fff;          /* white plate so dark logos stay visible */
    border-radius: 8px;
}

.h-sponsors__wall--static img {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
}
</style>