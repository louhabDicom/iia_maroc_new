{{--
    A sponsor logo.

    Falls back to the sponsor's name as text when no file has been uploaded, so
    the page never shows a broken image. `alt` is empty because the name is
    rendered as text right next to it in the accessible version; a logo whose alt
    repeats the adjacent link text is announced twice.

    Sized by the template's .brand-item rather than a fixed pixel height: the
    partner row is a Bootstrap grid of four columns whose width changes at every
    breakpoint, and a fixed height is what makes a wide wordmark overflow its
    cell or a tall crest shrink to nothing next to it.
--}}
@props(['sponsor'])

@php
    $path = $sponsor->isTopTier()
        ? ($sponsor->logo_path ?: $sponsor->logo_mono_path)
        : ($sponsor->logo_mono_path ?: $sponsor->logo_path);
@endphp

@if ($path)
    <img src="{{ \Illuminate\Support\Str::startsWith($path, 'http') ? $path : asset('storage/'.$path) }}"
         alt=""
         class="brand-item__logo"
         loading="lazy">
@else
    {{-- No file: the name carries the identification, so it has to be real text
         and it has to be selectable — a visitor looking for a partner's name is
         often looking for a word to search, not a picture. --}}
    <span class="brand-item__name">{{ $sponsor->name }}</span>
@endif
