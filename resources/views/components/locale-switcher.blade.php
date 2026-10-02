@props([
    'variant' => 'bar',
])

@php
    // The current page's path WITHOUT the install subfolder and WITHOUT the
    // locale prefix: "programme", "speakers", or "" for the home page.
    // request()->segments() is relative to the app root, so
    // /iia_maroc_new/public/fr/programme gives ['fr', 'programme'].
    $segments = request()->segments();
    if (isset($segments[0]) && array_key_exists($segments[0], $availableLocales)) {
        array_shift($segments);
    }
    $returnPath = implode('/', $segments);
@endphp

<form method="POST" action="{{ route('locale.switch') }}" class="d-lang">
    @csrf

    {{-- Clean return path, so the controller never has to parse the referer. --}}
    <input type="hidden" name="return_path" value="{{ $returnPath }}">
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