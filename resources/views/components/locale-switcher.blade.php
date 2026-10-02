{{--
    The language control.

    A segmented control — AR / FR / EN — rather than a dropdown.

    Three languages is three options. A dropdown makes the visitor open a panel
    in order to choose between three things that could have been visible all
    along, and it hides which language they are currently reading. All three
    shown at once, with the current one marked, is one tap instead of two and
    answers "am I in the right language?" without interacting at all.

    It is a form because switching language writes to the session: a GET that
    mutates state can be triggered by a third-party page with an <img> tag. Each
    option is a real <button name="switch_to">, so the request is a genuine
    POST with a CSRF token, the controller re-derives the return path from a
    same-origin referer rather than trusting anything the client sends, and the
    control works with scripting disabled.

    Each label is the language's own code — `dir="ltr"` on all three because a
    two-letter code is Latin script even when the language it names is Arabic.
    The accessible name is the full language name, so a screen reader announces
    "Arabic" rather than "A R".
--}}
@props([
    'variant' => 'bar',
])

<form method="POST" action="{{ route('locale.switch') }}" class="d-lang">
    @csrf

    <ul class="d-lang__list">
        @foreach ($availableLocales as $code => $label)
            @php($isCurrent = $code === $currentLocale->value)

            <li class="d-lang__item">
                <button type="submit"
                        name="switch_to"
                        value="{{ $code }}"
                        class="d-lang__btn"
                        dir="ltr"
                        @if ($isCurrent) aria-current="true" @endif>
                    <span aria-hidden="true">{{ strtoupper($code) }}</span>
                    <span class="visually-hidden">{{ $label }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    {{-- With scripting off the buttons still submit, because they are real
         submit buttons rather than links driven by a change handler. Nothing to
         add here — which is the point of the design. --}}
</form>
