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
            <div class="h-sponsors__wall h-sponsors__wall--single">
                <img src="{{ asset((string) ($fallback['src'] ?? '')) }}"
                     width="{{ $fallback['width'] ?? 349 }}"
                     height="{{ $fallback['height'] ?? 800 }}"
                     alt="{{ __('site.organiser') }}"
                     loading="lazy"
                     decoding="async">
            </div>
        @endif

    </div>
</section>