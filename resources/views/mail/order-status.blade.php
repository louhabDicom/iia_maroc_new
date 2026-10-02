{{--
    The order-status notification.

    Rendered as the text/plain part of the mail: a bank or mail scanner that
    mangles a decorated HTML template can mangle the one line that matters,
    which is the reference. Plain text also renders correctly in every client,
    including the text-mode ones a corporate mail gateway sometimes forces.

    No `{!! !!}` anywhere. A translated string is data, not markup, and echoing
    it raw would make a stray angle bracket in a language file an injection
    vector in every outgoing mail.
--}}
@php
    $paid = $event === 'paid';
@endphp
@if ($paid)
{{ __('order.mail.paid_intro', ['reference' => $order->reference], $locale) }}

{{ __('order.reference', [], $locale) }}: {{ $order->reference }}
{{ __('order.invoice.title', ['number' => $invoiceNumber ?? '—'], $locale) }}: {{ $invoiceNumber ?? '—' }}
{{ __('order.total', [], $locale) }}: {{ $total }}
{{ __('order.placed_on', [], $locale) }}: {{ $order->created_at->format('d/m/Y H:i') }}

{{ __('order.participants', [], $locale) }}:
@foreach ($order->participants as $participant)
  - {{ $participant->full_name }}@if ($participant->is_member) ({{ __('pricing.member', [], $locale) }})@endif
@endforeach

{{ $invoiceNumber ? __('order.mail.invoice_attached', [], $locale) : __('order.mail.invoice_online', [], $locale) }}
@else
{{ __('order.mail.failed_intro', ['reference' => $order->reference], $locale) }}

{{ __('order.reference', [], $locale) }}: {{ $order->reference }}
{{ __('order.total', [], $locale) }}: {{ $total }}

{{ __('order.mail.failed_action', [], $locale) }}
@endif

{{ __('order.mail.footer', [], $locale) }}