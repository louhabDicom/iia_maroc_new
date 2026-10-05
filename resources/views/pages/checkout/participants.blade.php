{{--
    One entry per place, server-rendered.

    `$participantValues` rather than `old()` so this can be rendered twice: from
    a full page load, where the flashed input is the only copy of what the buyer
    typed, and from the JSON that a "+" click replaces it with, where the values
    arrived in the request itself. One template for both means the two cannot
    drift into rendering different fields.

    Not built in JavaScript. The 2024 form built its participant rows in JS, so a
    visitor with scripting disabled — or a screenshot pasted into a message —
    produced a silently empty registration.
--}}
@php
    $participantValues = $participantValues ?? [];
@endphp

@for ($i = 0; $i < $quote->participantCount; $i++)
    @php $isMemberSlot = $i < $quote->memberCount; @endphp

    <fieldset class="border rounded p-3 mb-3">
        <legend class="float-none w-auto px-2 fs-6">
            {{ __('order.participant_number', ['number' => $i + 1]) }}
            @if ($isMemberSlot)
                <span class="badge bg-success">@lang('pricing.member')</span>
            @endif
        </legend>

        <div class="row">
            <div class="col-md-12">
                <x-form.field :name="'participants.'.$i.'.full_name'"
                              :label="__('order.participant_name')" required>
                    <input type="text" required dir="auto"
                           name="participants[{{ $i }}][full_name]"
                           id="participants-{{ $i }}-full_name"
                           value="{{ data_get($participantValues, $i.'.full_name') }}"
                           class="form-control">
                </x-form.field>
            </div>

            <div class="col-md-6">
                <x-form.field :name="'participants.'.$i.'.job_title'"
                              :label="__('register.job_title')">
                    <input type="text" dir="auto"
                           name="participants[{{ $i }}][job_title]"
                           id="participants-{{ $i }}-job_title"
                           value="{{ data_get($participantValues, $i.'.job_title') }}"
                           class="form-control">
                </x-form.field>
            </div>

            <div class="col-md-6">
                <x-form.field :name="'participants.'.$i.'.email'"
                              :label="__('register.email')">
                    <input type="email" dir="ltr"
                           name="participants[{{ $i }}][email]"
                           id="participants-{{ $i }}-email"
                           value="{{ data_get($participantValues, $i.'.email') }}"
                           class="form-control">
                </x-form.field>
            </div>

            <div class="col-md-6">
                <x-form.field :name="'participants.'.$i.'.phone'"
                              :label="__('register.phone')">
                    <input type="tel" dir="ltr"
                           name="participants[{{ $i }}][phone]"
                           id="participants-{{ $i }}-phone"
                           value="{{ data_get($participantValues, $i.'.phone') }}"
                           class="form-control">
                </x-form.field>
            </div>
        </div>
    </fieldset>

    @error('participants.'.$i.'.full_name')
        <p class="field-error" role="alert">{{ $message }}</p>
    @enderror
@endfor
