<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye HMS Notification</title>
</head>
<body style="margin:0;padding:0;background-color:#F0F4F8;font-family:Arial,Helvetica,sans-serif">

<table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="border-collapse:collapse">
    <tr>
        <td style="padding:30px 15px">
            <table role="presentation" cellspacing="0" cellpadding="0" style="max-width:600px;margin:0 auto;width:100%;border-collapse:collapse">

                {{-- ===== HEADER ===== --}}
                <tr>
                    <td style="background-color:#1B4F72;padding:24px 30px;border-radius:10px 10px 0 0">
                        <span style="font-size:20px;font-weight:bold;color:#FFFFFF">&#128065; Eye HMS SaaS</span>
                        <div style="font-size:12px;color:#A8D8EA;margin-top:3px">Multi-Tenant Hospital Management Platform</div>
                    </td>
                </tr>

                {{-- ===== ALERT BANNER ===== --}}
                @php
                    $bannerMap = [
                        'trial_welcome'   => ['bg' => '#D5F5E3', 'text' => '#1A6F5B', 'icon' => '&#127881;', 'label' => 'Free Trial Started — 14 Days'],
                        'reminder_7d'     => ['bg' => '#FFF3E0', 'text' => '#E65100', 'icon' => '&#9201;',   'label' => 'Subscription Expires in 7 Days'],
                        'reminder_3d'     => ['bg' => '#FFF3CD', 'text' => '#856404', 'icon' => '&#9888;',   'label' => 'Subscription Expires in 3 Days'],
                        'reminder_1d'     => ['bg' => '#FADBD8', 'text' => '#641E16', 'icon' => '&#128680;', 'label' => 'Subscription Expires Tomorrow!'],
                        'expired'         => ['bg' => '#FDEBD0', 'text' => '#784212', 'icon' => '&#128993;', 'label' => 'Subscription Expired — Grace Period Active'],
                        'grace_day4'      => ['bg' => '#FFF3E0', 'text' => '#E65100', 'icon' => '&#9888;',   'label' => 'Grace Period Ends in 3 Days'],
                        'grace_ended'     => ['bg' => '#FADBD8', 'text' => '#641E16', 'icon' => '&#128308;', 'label' => 'Account Deactivated — Data is Safe'],
                        'inactive_30d'    => ['bg' => '#FADBD8', 'text' => '#641E16', 'icon' => '&#9888;',   'label' => 'Data Deletion Warning'],
                        'renewal_success' => ['bg' => '#D5F5E3', 'text' => '#1A6F5B', 'icon' => '&#10004;', 'label' => 'Subscription Renewed Successfully'],
                        'suspended'       => ['bg' => '#FADBD8', 'text' => '#641E16', 'icon' => '&#128308;', 'label' => 'Account Suspended'],
                    ];
                    $banner = $bannerMap[$emailType] ?? ['bg' => '#D6EAF8', 'text' => '#154360', 'icon' => '&#8505;', 'label' => 'Platform Notification'];
                @endphp
                <tr>
                    <td style="background-color:{{ $banner['bg'] }};padding:14px 30px;border-left:4px solid {{ $banner['text'] }}">
                        <span style="font-size:14px;font-weight:bold;color:{{ $banner['text'] }}">
                            {!! $banner['icon'] !!}&nbsp;{{ $banner['label'] }}
                        </span>
                    </td>
                </tr>

                {{-- ===== BODY ===== --}}
                <tr>
                    <td style="background-color:#FFFFFF;padding:30px">

                        <p style="margin:0 0 16px;font-size:15px;color:#1A202C">
                            Dear <strong>{{ $tenant->admin_name ?? 'Hospital Admin' }}</strong>,
                        </p>

                        @switch($emailType)

                            @case('trial_welcome')
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    Welcome to <strong>Eye HMS SaaS</strong>! Your hospital <strong>{{ $tenant->name }}</strong> has been successfully registered and your <strong>14-day free trial</strong> has started.
                                </p>
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    All features are available — add your doctors, receptionists, and start registering patients today.
                                </p>
                                @break

                            @case('reminder_7d')
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    This is a friendly reminder that the subscription for <strong>{{ $tenant->name }}</strong> will expire in <strong>7 days</strong>.
                                </p>
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    Please renew your subscription now to ensure uninterrupted access to all HMS features.
                                </p>
                                @break

                            @case('reminder_3d')
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    <strong>Action Required:</strong> The subscription for <strong>{{ $tenant->name }}</strong> will expire in just <strong>3 days</strong>.
                                </p>
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    Renew immediately to ensure continuous access for your entire team.
                                </p>
                                @break

                            @case('reminder_1d')
                                <p style="margin:0 0 14px;font-size:14px;color:#C0392B">
                                    <strong>Final Warning:</strong> The subscription for <strong>{{ $tenant->name }}</strong> expires <strong>tomorrow</strong>.
                                </p>
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    After expiry your account enters a 7-day grace period (read-only access). Renew now to avoid disruption.
                                </p>
                                @break

                            @case('expired')
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    The subscription for <strong>{{ $tenant->name }}</strong> has expired. Your account has entered a <strong>7-day grace period</strong>.
                                </p>
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    During the grace period, your data is safe and you can view existing records, but you cannot register new patients. Renew now to restore full access.
                                </p>
                                @break

                            @case('grace_day4')
                                <p style="margin:0 0 14px;font-size:14px;color:#E67E22">
                                    <strong>Urgent:</strong> The grace period for <strong>{{ $tenant->name }}</strong> ends in <strong>3 days</strong>.
                                </p>
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    After the grace period ends your account will be deactivated. Your data will be retained for 30 days, but HMS access will be suspended.
                                </p>
                                @break

                            @case('grace_ended')
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    The grace period for <strong>{{ $tenant->name }}</strong> has ended. Your account has been <strong>deactivated</strong>.
                                </p>
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    Your hospital data is <strong>safe and retained for 30 days</strong>. Renew your subscription to reactivate your account.
                                </p>
                                @break

                            @case('inactive_30d')
                                <p style="margin:0 0 14px;font-size:14px;color:#C0392B">
                                    <strong>Data Deletion Warning:</strong> Your account for <strong>{{ $tenant->name }}</strong> has been inactive for 30 days.
                                </p>
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    Your hospital data is scheduled for permanent deletion. Please renew your subscription <strong>immediately</strong> to preserve all your records.
                                </p>
                                @break

                            @case('renewal_success')
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    Your subscription for <strong>{{ $tenant->name }}</strong> has been renewed successfully. Your account is fully active.
                                </p>
                                @break

                            @case('suspended')
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    Your account for <strong>{{ $tenant->name }}</strong> has been <strong>suspended</strong> by the platform administrator.
                                </p>
                                <p style="margin:0 0 14px;font-size:14px;color:#1A202C">
                                    Please contact our support team to resolve this issue.
                                </p>
                                @break

                        @endswitch

                        {{-- CTA Button --}}
                        @if(!in_array($emailType, ['grace_ended', 'suspended']))
                        <div style="text-align:center;margin:24px 0">
                            <a href="{{ url('/' . $tenant->slug . '/login') }}"
                               style="display:inline-block;padding:13px 32px;background-color:#27AE60;color:#FFFFFF;text-decoration:none;border-radius:6px;font-size:14px;font-weight:bold">
                                {{ in_array($emailType, ['trial_welcome', 'renewal_success']) ? '&#128640; Go to Dashboard' : '&#128179; Renew Subscription' }}
                            </a>
                        </div>
                        @endif

                        {{-- Hospital Details Box --}}
                        <hr style="border:none;border-top:1px solid #E2E8F0;margin:20px 0">
                        <table role="presentation" cellspacing="0" cellpadding="0" width="100%" style="background:#F0F4F8;border-radius:6px;border-collapse:collapse">
                            <tr>
                                <td style="padding:14px 16px">
                                    <div style="font-size:11px;color:#4A5568;font-weight:bold;letter-spacing:0.5px;margin-bottom:8px;text-transform:uppercase">Hospital Details</div>
                                    <table role="presentation" cellspacing="0" cellpadding="0" style="border-collapse:collapse">
                                        <tr>
                                            <td style="font-size:12px;color:#4A5568;padding-bottom:4px;width:110px">Hospital</td>
                                            <td style="font-size:12px;font-weight:600;color:#1A202C;padding-bottom:4px">{{ $tenant->name }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:12px;color:#4A5568;padding-bottom:4px">Login URL</td>
                                            <td style="font-size:12px;color:#2980B9;padding-bottom:4px">{{ url('/' . $tenant->slug . '/login') }}</td>
                                        </tr>
                                        @if($tenant->city || $tenant->state)
                                        <tr>
                                            <td style="font-size:12px;color:#4A5568;padding-bottom:4px">Location</td>
                                            <td style="font-size:12px;color:#1A202C;padding-bottom:4px">{{ implode(', ', array_filter([$tenant->city, $tenant->state])) }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                {{-- ===== FOOTER ===== --}}
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
