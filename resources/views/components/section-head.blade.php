{{--
    A section heading: eyebrow, title, lede.

    Used by every band on every page so that section rhythm is one decision made
    once rather than a heading style re-typed eight times.

    The heading level is a prop rather than hardcoded to <h2>: a page cannot have
    an <h3> before an <h2>, and the speakers page nests a keynote heading inside a
    section that already owns an <h2>. Pass `level` to keep the outline valid.

    `id` defaults to nothing; pass it when the section is labelled by
    `aria-labelledby` from elsewhere in the page.
--}}
@props([
    'eyebrow' => null,
    'title',
    'lede' => null,
    'level' => 2,
    'align' => 'start',
    'onDark' => false,
    'id' => null,
    'tag' => 'div',
])

@php
    // Only 2-6 are legal heading levels; anything else silently produces an
    // outline the browser repairs in a way we cannot predict.
    $level = min(6, max(2, (int) $level));
    $headingId = $id ?: 'section-'.substr(md5($title.'|'.($eyebrow ?? '')), 0, 8);
@endphp

<{{ $tag }} @class([
        'ux-head',
        'ux-head--center' => $align === 'center',
        'ux-head--on-dark' => $onDark,
    ])
    @if ($id) id="{{ $id }}" @endif>

    @if ($eyebrow)
        {{-- Gradient on a light section, solid gold on a dark one: a gold eyebrow
             is invisible against a photograph. --}}
        <span @class(['ux-eyebrow', 'ux-eyebrow--gradient' => ! $onDark])>
            {{ $eyebrow }}
        </span>
    @endif

    <{{ 'h'.$level }} @class(['ux-head__title']) @if ($id) id="{{ $headingId }}" @endif>
        {{ $title }}
    </{{ 'h'.$level }}>

    @if ($lede)
        <p class="ux-head__lede">{{ $lede }}</p>
    @endif
</{{ $tag }}>