{{-- resources/views/emails/newsletter-confirm.blade.php --}}
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('newsletter.mail_subject', [], $locale) }}</title>
</head>
<body style="margin:0;padding:0;background:#f6f5fd;font-family:'Segoe UI',Arial,sans-serif;color:#1b1464;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f5fd;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                       style="max-width:560px;background:#ffffff;border:1px solid #e4e1f4;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#2b1d9a;padding:28px 32px;color:#ffffff;font-size:20px;font-weight:700;">
                            {{ trans('site.site_name', [], $locale) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:16px;line-height:1.7;">
                            <p style="margin:0 0 16px;font-size:20px;font-weight:700;color:#2f1f9c;">
                                {{ trans('newsletter.mail_title', [], $locale) }}
                            </p>
                            <p style="margin:0 0 28px;">{{ trans('newsletter.mail_intro', [], $locale) }}</p>

                            <p style="margin:0 0 28px;">
                                <a href="{{ $url }}"
                                   style="display:inline-block;padding:14px 32px;background:#4f3cc9;color:#ffffff;text-decoration:none;font-weight:700;border-radius:999px;">
                                    {{ trans('newsletter.mail_button', [], $locale) }}
                                </a>
                            </p>

                            <p style="margin:0;font-size:13px;color:#5f6384;">
                                {{ trans('newsletter.mail_ignore', [], $locale) }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>