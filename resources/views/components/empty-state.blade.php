{{--
    Empty state.

    A deliberate, styled absence — used where a set is genuinely expected to fill
    up (the speaker list before line-up is confirmed, a partner tier before a
    renewal) rather than where the page itself has nothing to say.

    The distinction matters: a page-level "nothing here" should use the layout's
    own shell, because a dashed circle in the middle of an otherwise empty page
    reads as a rendering failure rather than as an answer.

    `icon` is a Font Awesome class. It is decorative and aria-hidden; the message
    beside it is the content.
--}}
@props([
    'message',
    'icon' => 'fa-regular fa-calendar-xmark',
])

<div class="ux-empty">
    <span class="ux-empty__mark" aria-hidden="true">
        <i class="fas {{ $icon }}"></i>
    </span>

    <p class="ux-empty__text">{{ $message }}</p>

    {{ $slot }}
</div>