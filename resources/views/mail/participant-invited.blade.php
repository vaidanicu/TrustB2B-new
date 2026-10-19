<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $eventName }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f1ea; font-family:Georgia, 'Times New Roman', serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f1ea; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="padding:28px 28px 8px 28px;">
                            <p style="margin:0 0 4px 0; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#8a8577;">{{ config('app.name') }}</p>
                            <h1 style="margin:0; font-size:20px; line-height:1.3; color:#1f2937;">{{ $eventName }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 0 28px; font-size:14px; line-height:1.6; color:#374151;">
                            <p>{{ $greeting }}</p>
                            <p>{{ $invites }}</p>
                            <p>{{ $confirm }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;">
                            <a href="{{ $invitationUrl }}" style="display:inline-block; background-color:#1f2937; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:6px; font-size:13px; letter-spacing:0.05em; text-transform:uppercase; font-family:Arial, sans-serif;">
                                {{ $button }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 28px 28px; font-size:12px; color:#8a8577; line-height:1.5;">
                            <p>{{ $linkNotice }}</p>
                            <p style="margin-top:16px;">{{ $regards }}<br>{{ config('app.name') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
