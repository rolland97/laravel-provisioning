{{-- laravel-provisioning: the invite. Plain, inline-styled HTML so it renders in any
     mail client without the host's mail layout. Publish `provisioning-views` to
     restyle, or set `provisioning.invite.mail_view` to your own view (same data). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:24px; background-color:#f4f4f5; font-family:-apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#18181b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; margin:0 auto; background-color:#ffffff; border-radius:8px;">
        <tr>
            <td style="padding:32px;">
                <p style="margin:0 0 8px; font-size:15px; line-height:1.6;">{{ $greeting }}</p>
                <p style="margin:0; font-size:15px; line-height:1.6;">{{ $bodyText }}</p>
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0 0;">
                    <tr>
                        <td style="background-color:#18181b; border-radius:8px;">
                            <a href="{{ $actionUrl }}" style="display:inline-block; padding:13px 28px; color:#ffffff; font-size:15px; font-weight:bold; text-decoration:none;">{{ $actionLabel }}</a>
                        </td>
                    </tr>
                </table>
                <p style="margin:14px 0 0; font-size:13px; line-height:1.6; color:#52525b;">{{ $expiryText }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
