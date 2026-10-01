<!DOCTYPE html>
{{--
    The text/plain alternative carries the substance. A contact form is read on
    phones and by screen readers, and a message that exists only as an HTML
    table is unusable there, so both parts say the same thing.
--}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>@lang('contact.mail_title', ['subject' => $subjectLine ?? __('contact.default_subject')])</title>
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:8px;">
    <tr>
        <td style="padding:24px 28px;border-bottom:1px solid #e2e8f0;">
            <h1 style="margin:0;font-size:18px;line-height:1.4;">
                @lang('contact.mail_title', ['subject' => $subjectLine ?? __('contact.default_subject')])
            </h1>
        </td>
    </tr>

    <tr>
        <td style="padding:24px 28px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="font-size:14px;line-height:1.6;color:#334155;">
                <tr>
                    <td style="padding:2px 0;width:110px;color:#64748b;">@lang('contact.name')</td>
                    <td style="padding:2px 0;font-weight:600;">{{ $senderName }}</td>
                </tr>
                <tr>
                    {{-- dir="ltr": an address must not be bidi-reordered when the
                         surrounding mail is Arabic. --}}
                    <td style="padding:2px 0;color:#64748b;">@lang('contact.email')</td>
                    <td style="padding:2px 0;" dir="ltr">
                        <a href="mailto:{{ $senderEmail }}" style="color:#0f766e;">{{ $senderEmail }}</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:0 28px 24px;">
            {{-- `white-space:pre-wrap` preserves the line breaks the sender typed;
                 `nl2br` alone would not survive a mail client reflowing it. --}}
            <div style="padding:16px;background:#f8fafc;border-inline-start:3px solid #0f766e;border-radius:4px;font-size:14px;line-height:1.7;white-space:pre-wrap;">{{ $body }}</div>
        </td>
    </tr>

    <tr>
        <td style="padding:16px 28px;border-top:1px solid #e2e8f0;font-size:12px;color:#64748b;">
            @lang('contact.mail_footer')
        </td>
    </tr>
</table>
</body>
</html>
