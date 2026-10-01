{{--
    One speaker.

    The biography is only rendered when it is actually usable: `hasValidBiography()`
    rejects the empty and single-sentence stubs that came across from the 2024
    data, because a card reading "speaker" with an empty paragraph looks like a
    mistake on a published page.

    On the template's .single-speaker markup, which supplies the framed portrait
    and the corner shape. `--large` switches between the grid card and the wide
    card used on the speakers page.
--}}
<li @class(['single-speaker', 'single-speaker--large' => $large ?? false])>

    <div class="speaker-thumb">
        @if ($speaker->photo_path)
            <img src="{{ \Illuminate\Support\Str::startsWith($speaker->photo_path, 'http')
                        ? $speaker->photo_path
                        : asset('storage/'.$speaker->photo_path) }}"
                 alt="{{ $speaker->fullName() }}"
                 loading="lazy">
        @else
            {{-- Initials rather than a broken image or a silhouette: it identifies
                 the person and does not imply a photo exists. --}}
            <span class="speaker-thumb__initials" aria-hidden="true">
                {{ $speaker->initials() }}
            </span>
        @endif
    </div>

    <div class="speaker-content">
        <h3 class="speaker-name">{{ $speaker->fullName() }}</h3>

        @if ($speaker->job_title || $speaker->organisation)
            <p class="speaker-role">
                @if ($speaker->job_title)<span>{{ $speaker->job_title }}</span>@endif
                @if ($speaker->job_title && $speaker->organisation)<span class="mx-1" aria-hidden="true">&middot;</span>@endif
                @if ($speaker->organisation)<span>{{ $speaker->organisation }}</span>@endif
            </p>
        @endif

        @if (($large ?? false) && $speaker->hasValidBiography())
            <p class="speaker-bio">{{ $speaker->biography }}</p>
        @endif

        @if ($speaker->sessions->isNotEmpty())
            <p class="speaker-sessions">
                {{ __('speakers.sessions') }}:
                {{ $speaker->sessions->pluck('title')->filter()->take(2)->join(' · ') }}
            </p>
        @endif
    </div>

    <div class="speaker-shape">
        <img src="{{ asset('assets/images/shape/speaker_shape1.png') }}" alt="">
    </div>
</li>
