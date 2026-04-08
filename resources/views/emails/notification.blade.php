<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notificationSubject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#F0F4F8;font-family:Arial,Helvetica,sans-serif">

<table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="border-collapse:collapse">
    <tr>
        <td style="padding:30px 15px">
            <table role="presentation" cellspacing="0" cellpadding="0" style="max-width:600px;margin:0 auto;width:100%;border-collapse:collapse">

                {{-- Header --}}
                <tr>
                    <td style="background-color:#1B4F72;padding:24px 30px;border-radius:10px 10px 0 0">
                        <span style="font-size:20px;font-weight:bold;color:#FFFFFF">&#128065; Eye HMS SaaS</span>
                        <div style="font-size:12px;color:#A8D8EA;margin-top:3px">Platform Administration — Important Notice</div>
                    </td>
                </tr>

                {{-- Subject Banner --}}
                <tr>
                    <td style="background-color:#2980B9;padding:12px 30px">
                        <span style="font-size:13px;font-weight:bold;color:#FFFFFF">{{ $notificationSubject }}</span>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background-color:#FFFFFF;padding:30px">
                        <p style="margin:0 0 16px;font-size:15px;color:#1A202C">
                            Dear <strong>{{ $tenant->admin_name ?? 'Hospital Admin' }}</strong>,
                        </p>

                        <div style="font-size:14px;color:#1A202C;line-height:1.7;white-space:pre-wrap">{{ $notificationBody }}</div>

                        <hr style="border:none;border-top:1px solid #E2E8F0;margin:24px 0">

                        <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="background:#F0F4F8;border-radius:6px;border-collapse:collapse">
                            <tr>
                                <td style="padding:14px 16px">
                                    <div style="font-size:11px;color:#4A5568;font-weight:bold;letter-spacing:0.5px;margin-bottom:8px;text-transform:uppercase">Your Hospital</div>
                                    <table role="presentation" cellspacing="0" cellpadding="0" style="border-collapse:collapse">
                                        <tr>
                                            <td style="font-size:12px;color:#4A5568;padding-bottom:4px;width:110px">Hospital</td>
                                            <td style="font-size:12px;font-weight:600;color:#1A202C;padding-bottom:4px">{{ $tenant->name }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:12px;color:#4A5568;padding-bottom:4px">Login URL</td>
                                            <td style="font-size:12px;color:#2980B9;padding-bottom:4px">{{ url('/' . $tenant->slug . '/login') }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background-color:#0D2137;padding:20px 30px;border-radius:0 0 10px 10px">
                        <p style="margin:0;font-size:12px;color:#A8D8EA;text-align:center">
                            This email was sent by Eye HMS SaaS Platform &nbsp;|&nbsp;
                            <a href="{{ url('/') }}" style="color:#1ABC9C;text-decoration:none">hmssaas.com</a>
                        </p>
                        <p style="margin:8px 0 0;font-size:11px;color:#4A5568;text-align:center">
                            Made with &#10084;&#65039; in India &nbsp;|&nbsp; &copy; {{ date('Y') }} Eye HMS SaaS
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
