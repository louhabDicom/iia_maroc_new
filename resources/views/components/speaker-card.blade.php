{{--
    One speaker, on the new person card.

    The biography is only rendered when it is actually usable:
    `hasValidBiography()` rejects the empty and single-sentence stubs that came
    across from the 2024 data, because a card with an empty paragraph under the
    name looks like a mistake on a published page.

    `variant="wide"` is the keynote layout — portrait beside the text rather than
    above it — so the headline act is visibly a different weight from the rest of
    the line-up.

    The photo is lazy-loaded and given explicit dimensions by its container's
    aspect-ratio, so the card does not reflow as eighteen portraits arrive on a
    phone. `decoding="async"` hands the decode off the main thread for the same
    reason.
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

<li @class([
        'ux-person',
        'ux-person--keynote' => $isKeynote,
        'ux-person--wide' => $variant === 'wide',
    ])>

    <div class="ux-person__media">
        @if ($photo)
            <img src="{{ $photo }}"
                 alt="{{ $speaker->fullName() }}"
                 width="400"
                 height="400"
                 loading="lazy"
                 decoding="async">
        @else
            {{-- Initials rather than a broken image or a silhouette: it identifies
                 the person and does not imply a photo exists. --}}
            <span class="ux-person__initials" aria-hidden="true">
                {{ $speaker->initials() }}
            </span>
        @endif
    </div>

    <div class="ux-person__body">
        <h3 class="ux-person__name">{{ $speaker->fullName() }}</h3>

        @if ($speaker->job_title)
            <p class="ux-person__role">{{ $speaker->job_title }}</p>
        @endif

        @if ($speaker->organisation)
            <p class="ux-person__org">{{ $speaker->organisation }}</p>
        @endif

        {{-- Only the keynote card has room for a biography, and only when the
             biography is real. --}}
        @if ($variant === 'wide' && $speaker->hasValidBiography())
            <p class="ux-person__bio">{{ $speaker->biography }}</p>
        @endif

        {{-- The footer is pinned to the bottom of the card, so a grid of speakers
             lines up along its bottom edge whether or not a card has sessions. --}}
        @if ($speaker->sessions->isNotEmpty())
            <div class="ux-person__foot">
                <span class="ux-tag ux-tag--muted">@lang('speakers.sessions')</span>

                <p class="ux-person__sessions">
                    {{ $speaker->sessions->pluck('title')->filter()->take(2)->join(' · ') }}
                </p>
            </div>
        @endif
    </div>
</li>
