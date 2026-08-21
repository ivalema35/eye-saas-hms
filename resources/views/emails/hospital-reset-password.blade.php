<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>

<body style="margin:0;padding:0;background:#F4F6F9;font-family:'Inter',Arial,sans-serif">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#F4F6F9;padding:40px 20px">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#FFFFFF;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow:hidden">
                    {{-- Header --}}
                    <tr>
                        <td
                            style="background:linear-gradient(135deg,#0D2137,#1B4F72);padding:28px 32px;text-align:center">
                            <span style="color:#1ABC9C;font-size:22px;font-weight:800;letter-spacing:-.5px">
                                Eye<span style="color:#FFFFFF">HMS</span>
                            </span>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px">
                            <h2 style="margin:0 0 8px;color:#0D2137;font-size:20px;font-weight:700">
                                Reset Your Password
                            </h2>
                            <p style="margin:0 0 20px;color:#64748B;font-size:14px;line-height:1.6">
                                You are receiving this email because we received a password reset request for your
                                account.
                            </p>

                            <table cellpadding="0" cellspacing="0" style="margin:24px 0">
                                <tr>
                                    <td
                                        style="background:#1B4F72;border-radius:8px;padding:14px 32px;text-align:center">
                                        <a href="{{ $url }}"
                                            style="color:#FFFFFF;text-decoration:none;font-size:15px;font-weight:700;display:inline-block">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;color:#64748B;font-size:13px;line-height:1.6">
                                This password reset link will expire in 60 minutes.
                            </p>
                            <p style="margin:0 0 20px;color:#64748B;font-size:13px;line-height:1.6">
                                If you did not request a password reset, no further action is required.
                            </p>

                            <hr style="border:none;border-top:1px solid #E5E7EB;margin:20px 0">
                            <p style="margin:0;color:#9CA3AF;font-size:12px;line-height:1.6">
                                If you're having trouble clicking the button, copy and paste the URL below into your
                                browser:<br>
                                <a href="{{ $url }}" style="color:#2980B9;word-break:break-all">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#0D2137;padding:20px 32px;text-align:center">
                            <span>&copy; {{ date('Y') }} EYENOSIS. All rights reserved. | Designed &amp; Developed by <a
                                    href="https://ivinfotech.com" target="_blank" rel="noopener noreferrer">IV
                                    Infotech</a></span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>