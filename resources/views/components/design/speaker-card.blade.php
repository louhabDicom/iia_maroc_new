{{--
    One speaker, on the design system.

    `x-speaker-card` renders `.ux-person` and is consumed by the home page, so it
    stays exactly as it is. This is the same speaker, built from the existing
    `.d-speaker*` primitives, and it is the <li> itself — callers put it straight
    inside a `.d-speakers` grid rather than wrapping it in a column <li>, which is
    what the 2024 markup did and which nests a list item inside a list item.

    `variant="wide"` is the keynote layout: portrait beside the text rather than
    above it, so the headline act is visibly a different weight from the rest of
    the line-up.

    The biography is rendered only when it is usable. `hasValidBiography()`
    rejects the empty and single-sentence stubs that came across from the 2024
    data, because a card with an empty paragraph under the name looks like a
    mistake on a published page.

    The photo is lazy-loaded and given explicit dimensions by its container's
    aspect-ratio, so the card does not reflow as eighteen portraits arrive on a
    phone.
--}}
@props([
    'speaker',
    'variant' => 'grid',
])

@php
    $isKeynote = $variant === 'wide' || $speaker->is_keynote;
    $photo = $speaker->photo_path
        ? (\Illuminate\Support\Str::startsWith($speaker->photo_path, 'http')
            ? $speaker->photo_path
            : asset('storage/'.$speaker->photo_path))
        : null;
@endphp

<li {{ $attributes->merge([
        'class' => 'd-card d-speaker'
            .($isKeynote ? ' d-speaker--keynote' : '')
            .($variant === 'wide' ? ' d-speaker--wide' : ''),
    ]) }}>


    <div class="d-speaker__body">
        <h3 class="d-speaker__name">{{ $speaker->fullName() }}</h3>

        @if ($speaker->job_title)
            <p class="d-speaker__role">{{ $speaker->job_title }}</p>
        @endif

        @if ($speaker->organisation)
            <p class="d-speaker__org">{{ $speaker->organisation }}</p>
        @endif

        @if ($variant === 'wide' && $speaker->hasValidBiography())
            <p class="d-speaker__bio">{{ $speaker->biography }}</p>
        @endif

        {{-- The footer is pinned to the bottom of the card, so a grid of speakers
             lines up along its bottom edge whether or not a card has sessions. --}}
        @if ($speaker->sessions->isNotEmpty())
            <div class="d-speaker__foot">
                <span class="d-tag">@lang('speakers.sessions')</span>

                <p class="d-speaker__sessions">
                    {{ $speaker->sessions->pluck('title')->filter()->take(2)->join(' · ') }}
                </p>
            </div>
        @endif
    </div>
</li>
