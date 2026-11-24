<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $subject }}</title>
    <style>
        @font-face {
            font-family: 'Magilio';
            src: url('{{ asset('fonts/Magilio.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Libron';
            src: url('{{ asset('fonts/Libron-Regular.woff2') }}') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#EEF3F8;font-family:'Libron',Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#EEF3F8;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td align="center" style="padding:36px 32px 0;">
                            <a href="{{ $appUrl }}" style="text-decoration:none;">
                                <img src="{{ asset('nastias-gallery-logo.png') }}" alt="{{ $appName }}" width="140" style="display:block;width:140px;height:auto;margin:0 auto;border:0;" />
                            </a>
                            <p style="margin:14px 0 0;font-family:'Magilio',Georgia,serif;font-size:20px;letter-spacing:0.06em;color:#293241;">{{ $appName }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 32px 0;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#8A94A6;text-align:center;">{{ $eyebrow }}</p>
                            <h1 style="margin:10px 0 0;font-family:'Magilio',Georgia,serif;font-size:26px;line-height:1.3;color:#293241;text-align:center;">{{ $heading }}</h1>
                            <p style="margin:12px 0 0;font-size:15px;line-height:1.6;color:#4A5568;text-align:center;">{{ $intro }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 32px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7FAFD;border:1px solid #E4EAF2;border-radius:8px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A94A6;">{{ $fromLabel }}</p>
                                        <p style="margin:6px 0 0;font-size:15px;font-weight:600;line-height:1.5;color:#293241;word-break:break-word;">{{ $senderName }}</p>
                                        <p style="margin:2px 0 0;font-size:14px;line-height:1.5;word-break:break-word;">
                                            <a href="mailto:{{ $senderEmail }}" style="color:#4A5568;text-decoration:none;">{{ $senderEmail }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px 0;">
                            <p style="margin:0;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#8A94A6;">{{ $messageLabel }}</p>
                            <div style="margin:8px 0 0;font-size:15px;line-height:1.65;color:#4A5568;word-break:break-word;">{!! nl2br(e($messageBody)) !!}</div>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:28px 32px 8px;">
                            <a href="mailto:{{ $senderEmail }}" style="display:inline-block;background-color:#293241;color:#FFFFFF;text-decoration:none;font-family:'Libron',Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;padding:13px 28px;border-radius:8px;">{{ $replyLabel }}</a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:8px 32px 24px;border-top:1px solid #E4EAF2;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#8A94A6;text-align:center;">
                                {{ $footer }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
