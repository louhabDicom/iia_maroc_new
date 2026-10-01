{{--
    A labelled form field with optional help text and an error slot.

    The error is rendered inside the component rather than in each call site, so
    a new field cannot be added without an accessible error. The ids match the
    pattern the call sites use for aria-describedby (e.g. "phone-error").

    `aria-invalid` is set only when there is an error, for the same reason the
    asterisk is paired with a visually hidden label: an error that is announced
    before the field has been read is not an error the visitor can act on.

    The error text sits in a live region so a client-side revalidation that adds
    an error is announced without moving focus.
--}}
@props([
    'name',
    'label',
    'help' => null,
    'required' => false,
])

@php
    $helpId = $help ? $name.'-help' : null;
    $errorId = $name.'-error';
    $describedBy = collect([$helpId, $errorId])->filter()->implode(' ');
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
            {{-- Announced by assistive tech, invisible on screen. The asterisk
                 alone is not enough: it is not read out and it is not conveyed
                 to a screen reader as a required field. --}}
            <span class="visually-hidden">@lang('misc.required_field')</span>
        @endif
    </label>

    <div class="mt-1">
        {{ $slot }}
    </div>

    @if ($help)
        <p id="{{ $helpId }}" class="form-text">{{ $help }}</p>
    @endif

    @error($name)
        <p id="{{ $errorId }}" class="field-error" role="alert">{{ $message }}</p>
    @enderror
</div>
