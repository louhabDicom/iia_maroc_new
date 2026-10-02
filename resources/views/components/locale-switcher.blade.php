{{--
    The language control — a segmented control, AR / FR / EN.

    It is a form because switching language writes to the session: a GET that
    mutates state can be triggered by a third-party page. Each option is a real
    <button name="switch_to">, so the request is a POST with a CSRF token and
    the control works with scripting disabled.

    The current page path is sent along (`return_path`), relative to the app
    root, so the controller does not depend on the Referer header. The
    controller validates it as a plain relative path before using it.
--}}
@props([
    'variant' => 'bar',
])

<form method="POST" action="{{ route('locale.switch') }}" class="d-lang">
    @csrf

    {{-- request()->path() excludes the install subfolder: "fr/programme" or "/" --}}
    <input type="hidden" name="return_path" value="{{ request()->path() }}">
    <input type="hidden" name="return_query" value="{{ request()->getQueryString() }}">

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
</form>