@lang('contact.mail_title', ['subject' => $subjectLine ?? __('contact.default_subject')])

@lang('contact.name'): {{ $senderName }}
@lang('contact.email'): {{ $senderEmail }}

{{ $body }}

--
@lang('contact.mail_footer')
