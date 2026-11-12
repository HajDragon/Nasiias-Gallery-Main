<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New artwork: {{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#EEF3F8;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EEF3F8;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px 0;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#8A94A6;">{{ config('app.name') }}</p>
                            <h1 style="margin:10px 0 0;font-size:22px;line-height:1.3;color:#293241;">New artwork: {{ $title }}</h1>
                        </td>
                    </tr>

                    @if ($imageUrl)
                        <tr>
                            <td style="padding:20px 32px 0;">
                                <img src="{{ $imageUrl }}" alt="{{ $altText }}" style="display:block;width:100%;height:auto;border-radius:8px;" />
                            </td>
                        </tr>
                    @endif

                    @if ($description)
                        <tr>
                            <td style="padding:20px 32px 0;">
                                <p style="margin:0;font-size:15px;line-height:1.6;color:#4A5568;">{{ $description }}</p>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:24px 32px 32px;">
                            <a href="{{ $url }}" style="display:inline-block;background-color:#293241;color:#FFFFFF;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px;">View artwork</a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 32px 24px;border-top:1px solid #E4EAF2;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#8A94A6;">
                                You are receiving this email because you opted in to new artwork notifications on {{ config('app.name') }}.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
