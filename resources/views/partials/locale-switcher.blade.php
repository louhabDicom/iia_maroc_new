{{--
    Language switcher.

    A form posting to the server, not a set of links, because the target has to
    be the page the visitor is currently on. The referer is the source of that,
    and LocaleController validates it against APP_URL rather than redirecting
    blindly — trusting a referer here would be an open redirect.

    The 2024 build used links (`index.php?lang=fr`). That is a GET, and a GET
    that writes to the session and to the visitor's stored preference is a state
    change a third-party page can trigger with an <img> tag. So this stays a
    CSRF-protected POST, and the template's dropdown is built around a control
    that can submit a form — a <select> that submits on change, which gives the
    same one-click behaviour without giving up the method.

    Each option is labelled in its own language (français / English / العربية)
    rather than in the current one, because a person looking for their language
    is scanning for the name of it, not for a translation of it. `lang` and
    `dir` are set per option so an Arabic label inside an LTR page is still
    ordered correctly.
--}}
<form method="POST" action="{{ route('locale.switch') }}"
      class="locale-switcher @class(['locale-switcher--menu' => ($inMenu ?? false)])">
    @csrf

    <label for="locale-switcher-{{ $inMenu ?? false ? 'menu' : 'page' }}"
           @unless ($inMenu ?? false) class="sr-only" @endunless>
        @lang('misc.change_language')
    </label>

    <select id="locale-switcher-{{ ($inMenu ?? false) ? 'menu' : 'page' }}"
            name="switch_to"
            onchange="this.form.submit()"
            @class([
                'form-select form-select-sm',
                'locale-switcher__select' => ! ($inMenu ?? false),
            ])>
        @foreach ($availableLocales as $code => $label)
            <option value="{{ $code }}"
                    @selected($code === $currentLocale->value)
                    lang="{{ $code }}"
                    dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}">
                {{ $label }}
            </option>
        @endforeach
    </select>

    {{-- With scripting off the select has no submit button of its own, so the
         control is inert and the visitor is stuck on the current language. The
         button is visually hidden rather than absent, and focusable, so it is
         reachable by keyboard. --}}
    <noscript>
        <button type="submit" class="btn btn-sm btn-outline-secondary mt-2 w-100">
            @lang('misc.change_language')
        </button>
    </noscript>
</form>
