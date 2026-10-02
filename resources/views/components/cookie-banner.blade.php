{{--
    Cookie notice.

    `position: fixed`, so it does not push the page or leave a gap when it
    appears — a consent bar in the document flow reflows the whole site the
    moment it renders, which is why a first-time visitor sees the footer jump.

    The choice is stored in `localStorage` rather than a cookie because it is
    a UI preference about this browser, not something the server needs to read
    and setting a cookie here would be the very act the banner is asking about.
    `noscript` shows a plain statement instead of a control that cannot work.

    `role="region"` with a label, so it is announced as a region rather than
    being read as loose text at the end of the page.
--}}
<div class="d-cookie"
     data-cookie
     role="region"
     aria-label="{{ __('cookie.title') }}"
     hidden>

    <div class="container">
        <div class="d-cookie__row">

            <p class="d-cookie__text">
                <strong>{{ __('cookie.title') }}</strong>
                <span>{{ __('cookie.body') }}</span>
            </p>

            <div class="d-cookie__actions">
                <button type="button" class="d-btn d-btn--primary d-btn--sm" data-cookie-accept>
                    @lang('cookie.accept')
                </button>
                <button type="button" class="d-btn d-btn--on-dark d-btn--sm" data-cookie-decline>
                    @lang('cookie.decline')
                </button>
                <button type="button" class="d-cookie__close" data-cookie-dismiss
                        aria-label="{{ __('cookie.dismiss') }}">
                    <i class="fas fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

        </div>
    </div>
</div>

<noscript>
    <div class="d-cookie d-cookie--static" role="region" aria-label="{{ __('cookie.title') }}">
        <div class="container">
            <p class="d-cookie__text">
                <strong>{{ __('cookie.title') }}</strong>
                <span>{{ __('cookie.body') }}</span>
            </p>
        </div>
    </div>
</noscript>
