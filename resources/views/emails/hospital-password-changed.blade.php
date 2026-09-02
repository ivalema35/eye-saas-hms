<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed — EYENOSIS</title>
</head>

<body
    style="margin:0;padding:0;background:#F7FAFC;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0"
        style="max-width:600px;margin:2rem auto;background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <tr>
            <td
                style="padding:2rem 2.5rem;text-align:center;background:linear-gradient(135deg,#0070C0,#00B0A0);border-radius:12px 12px 0 0;">
                <h1 style="color:#fff;margin:0;font-size:1.5rem;font-weight:800;">Password Updated</h1>
                <p style="color:rgba(255,255,255,.85);margin:.5rem 0 0;font-size:.9rem;">Your hospital login password was changed</p>
            </td>
        </tr>
        <tr>
            <td style="padding:2rem 2.5rem;">
                <p style="font-size:.95rem;color:#1A202C;line-height:1.6;">
                    Hello <strong>{{ $tenant->admin_name }}</strong>,
                </p>
                <p style="font-size:.95rem;color:#1A202C;line-height:1.6;">
                    The Super Admin has updated the login password for your hospital
                    <strong>{{ $tenant->name }}</strong> on EYENOSIS.
                    Use the new credentials below to sign in.
                </p>

                <table width="100%" cellpadding="0" cellspacing="0"
                    style="background:#F7FAFC;border:1px solid #E2E8F0;border-radius:8px;margin:1.5rem 0;">
                    <tr>
                        <td style="padding:1.25rem;">
                            <p
                                style="margin:0 0 .5rem;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;color:#6B7280;font-weight:700;">
                                Your Login Details</p>
                            <table cellpadding="0" cellspacing="0" style="font-size:.9rem;color:#1A202C;">
                                <tr>
                                    <td style="padding:.25rem 1rem .25rem 0;font-weight:600;">Login URL:</td>
                                    <td style="padding:.25rem 0;">{{ url('/' . $tenant->slug . '/login') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:.25rem 1rem .25rem 0;font-weight:600;">Email:</td>
                                    <td style="padding:.25rem 0;">{{ $tenant->admin_email }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:.25rem 1rem .25rem 0;font-weight:600;">New Password:</td>
                                    <td style="padding:.25rem 0;font-weight:700;">{{ $newPassword }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <p style="font-size:.85rem;color:#92400E;background:#FEF3C7;border:1px solid #FDE68A;border-radius:8px;padding:.75rem 1rem;line-height:1.5;margin:0 0 1.25rem;">
                    If you did not expect this change, please contact support immediately.
                    After logging in, we recommend changing your password from profile settings.
                </p>

                <p style="text-align:center;margin:1.5rem 0;">
                    <a href="{{ url('/' . $tenant->slug . '/login') }}"
                        style="display:inline-block;padding:.75rem 2rem;background:#0070C0;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;font-size:.9rem;">
                        Login to Your Hospital
                    </a>
                </p>

                <p style="font-size:.85rem;color:#6B7280;line-height:1.6;">
                    If you have any questions, reply to this email or contact our support team.<br>
                    Thank you for choosing EYENOSIS!
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:1.25rem 2.5rem;text-align:center;border-top:1px solid #E2E8F0;">
                <span>
                    &copy; {{ date('Y') }}
                    <a href="https://eyenosis.com" target="_blank" rel="noopener noreferrer">
                        Eyenosis.com
                    </a>
                </span>
            </td>
        </tr>
    </table>
</body>

</html>
