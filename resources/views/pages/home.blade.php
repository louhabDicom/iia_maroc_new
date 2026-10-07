@extends('layouts.app')

@section('title', $edition->titleIn($locale))
@section('description', $edition->introduction)

@section('content')

    {{-- =====================================================================
        The landing page is a composition, not a document.

        Every band below lives in `components/home/`. That is partly taste and
        partly a hard constraint: Livewire installs a Blade precompiler that
        passes the whole template through PCRE as a *pattern*, and PCRE caps a
        pattern at roughly 32 KB. A single 37 KB landing page does not render
        at all — it 500s with "regular expression is too large", which says
        nothing about the cause.

        Splitting it is the correct structure anyway: each band takes exactly
        the data it prints, so a change to the tariff cards cannot break the
        hero, and each one can be read on its own.

        Order is the argument the page makes, and it is the order of the design:
        what this is → the pattern it belongs to → why it is worth the trip →
        what it costs → when it happens → who is speaking → who is backing it.
        The two pattern bands are separators, not decoration: they are the one
        place the motif is allowed to be the subject, and they sit exactly where
        the page changes register.

        The bands the previous build carried — video, gallery, organisers,
        venue, and the closing call to action — are not in this composition.
        Their components have been removed rather than left orphaned, since
        `x-home.*` was referenced from this file and nowhere else.
        ===================================================================== --}}

    <x-home.hero :edition="$edition" :locale="$locale" />

    <x-home.pattern-band />

    <x-home.stats :stats="$stats" :edition="$edition" />

    <x-home.pattern-band />

    <x-home.about :edition="$edition" />


<x-home.pricing
    :ticket-types="$ticketTypes"
    :edition="$edition"
    :is-member="$isMember"
    :registration-open="$registrationOpen"
    :can-order="$canOrder"
/>


    <x-home.programme
        :days="$days"
        :slots="$slots"
        :selected-day="$selectedDay"
        :locale="$locale"
        :programme-document="$programmeDocument"
        :pdf-url="$edition->programmePdfUrl($locale)"
        route-name="home" />

    <!-- <x-home.speakers :speakers="$speakers" /> -->

    <x-home.partners :sponsors="$sponsors" />

@endsection
