{{--
    Enrolling an authenticator app.

    The step numbers are not decoration. The failure this page has to survive is
    the visitor closing the tab halfway and coming back tomorrow, so it says
    plainly what has already happened and what has not: the account exists, the
    secret is not yet active, and nothing is lost by leaving and coming back.

    The QR code and the manual key are the same secret, printed side by side,
    rather than the key hidden behind a "can't scan?" link. The two people who
    need the key are exactly the ones who will not find the link: someone on a
    desktop with no phone camera to hand, and someone whose camera will not focus
    on a screen in a conference hall.
--}}
@extends('layouts.app')

@section('title', __('totp.title'))

@section('content')
    <div class="section-padding-04">
        <div class="container">
            <div class="app-shell app-shell--narrow">

                <h1 class="app-title">@lang('totp.heading')</h1>

                <p class="app-lede">@lang('totp.lede')</p>

                {{-- The state of the account, stated before the instructions
                     rather than discovered at the end. --}}
                <div class="app-notice mb-4">
                    @lang('totp.pending_notice')
                </div>

                <div class="app-card">
                    <ol class="app-steps">
                        <li>
                            <p class="app-note--strong">@lang('totp.step_install')</p>
                            <p class="app-note">@lang('totp.step_install_lede')</p>
                        </li>

                        <li>
                            <p class="app-note--strong">@lang('totp.step_scan')</p>
                            <p class="app-note">@lang('totp.step_scan_lede', ['issuer' => $issuer])</p>

                            @if ($qrCode !== '')
                                <div class="totp-qr">
                                    {{-- Inline SVG data URL, never a served image:
                                         this URI contains the shared secret, and a
                                         separate request would leave it in a cache
                                         where a shared machine could read it back. --}}
                                    <img src="{{ $qrCode }}"
                                         alt="@lang('totp.qr_alt', ['issuer' => $issuer])"
                                         width="240"
                                         height="240">
                                </div>
                            @endif
                        </li>

                        <li>
                            <p class="app-note--strong">@lang('totp.step_manual')</p>
                            <p class="app-note">@lang('totp.step_manual_lede')</p>

                            {{-- Forced LTR: the secret is base32, and bidi would
                                 present it in reverse on the Arabic page. --}}
                            <p class="totp-key" dir="ltr">{{ $manualKey }}</p>
                        </li>
                    </ol>

                    <form method="POST" action="{{ route('totp.confirm') }}" class="mt-4">
                        @csrf

                        <x-form.field name="code" :label="__('totp.code_label')" :help="__('totp.code_hint', ['digits' => $codeLength])" required>
                            {{-- One field, not six boxes: six separate inputs break
                                 paste-to-advance, block paste, and lose the value
                                 when autofill fills the first box. --}}
                            <input type="text"
                                   name="code"
                                   id="totp-code"
                                   required
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   maxlength="{{ $codeLength }}"
                                   dir="ltr"
                                   @class(['input', 'app-code', 'input-error' => $errors->has('code')])
                                   @if ($errors->has('code')) aria-invalid="true" aria-describedby="code-error" @endif>
                        </x-form.field>

                        <button type="submit" class="btn btn-primary w-100">@lang('totp.submit')</button>
                    </form>
                </div>

                {{-- Stated up front because it is the trade this method makes:
                     free and unlimited, but a delegate without a spare handset
                     and without the recovery codes is locked out. --}}
                <p class="app-note mt-4 text-center">@lang('totp.tradeoff')</p>
            </div>
        </div>
    </div>
@endsection
