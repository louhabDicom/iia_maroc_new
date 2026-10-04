{{--
    A section heading on the design system.

    `x-section-head` is shared with the pages outside this brief, so it stays.
    This is the same contract — `id`, `eyebrow`, `title`, `level`, `lede`,
    `align` — emitting the existing `.d-head` primitives.

    `id` goes on the heading rather than on the wrapper, because it exists to be
    the target of the section's `aria-labelledby`: pointing that at a <div> would
    leave the section announced by an empty string.

    `level` is clamped rather than trusted, because it is interpolated straight
    into a tag name.
--}}
@props([
    'title',
    'id' => null,
    'eyebrow' => null,
    'lede' => null,
    'level' => 2,
    'align' => 'start',
])

@php
    $headingTag = 'h'.min(6, max(1, (int) $level));
@endphp

<div {{ $attributes->merge(['class' => 'd-head'.($align === 'center' ? ' d-head--center' : '')]) }}>

    @if ($eyebrow)
        <span class="d-eyebrow">{{ $eyebrow }}</span>
    @endif

    <{{ $headingTag }} @if ($id) id="{{ $id }}" @endif class="d-head__title">
        {{ $title }}
    </{{ $headingTag }}>

    @if ($lede)
        <p class="d-head__lede">{{ $lede }}</p>
    @endif
</div>
