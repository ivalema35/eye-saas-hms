<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\NotificationMail;
use App\Models\Platform\PlatformNotification;
use App\Models\Platform\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * NotificationController — Super Admin
 *
 * Communication center for the platform admin.
 * Allows composing and sending custom email notifications
 * to all hospitals or a specific selection.
 *
 * ROUTES: superadmin.notifications.*
 * VIEW:   superadmin/notifications/index.blade.php
 */
class NotificationController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::whereNotNull('admin_email')
            ->whereNotIn('status', ['suspended'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'admin_email', 'status']);

        $recentNotifications = PlatformNotification::with('tenant')
            ->where('type', 'broadcast')
            ->latest()
            ->limit(50)
            ->get();

        return view('superadmin.notifications.index', compact('tenants', 'recentNotifications'));
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject'      => ['required', 'string', 'max:255'],
            'message'      => ['required', 'string', 'max:5000'],
            'recipient'    => ['required', 'in:all,specific'],
            'tenant_ids'   => ['required_if:recipient,specific', 'array'],
            'tenant_ids.*' => ['exists:tenants,id'],
        ]);

        $query = Tenant::whereNotNull('admin_email')
            ->whereNotIn('status', ['suspended']);

        if ($validated['recipient'] === 'specific') {
            $query->whereIn('id', $validated['tenant_ids']);
        }

        $tenants   = $query->get();
        $sentCount = 0;

        foreach ($tenants as $tenant) {
            try {
                Mail::to($tenant->admin_email)->queue(
                    new NotificationMail($tenant, $validated['subject'], $validated['message'])
                );

                PlatformNotification::create([
                    'tenant_id'       => $tenant->id,
                    'type'            => 'broadcast',
                    'subject'         => $validated['subject'],
                    'recipient_email' => $tenant->admin_email,
                    'status'          => 'sent',
                    'sent_at'         => now(),
                ]);

                $sentCount++;
            } catch (\Throwable $e) {
                PlatformNotification::create([
                    'tenant_id'       => $tenant->id,
                    'type'            => 'broadcast',
                    'subject'         => $validated['subject'],
                    'recipient_email' => $tenant->admin_email,
                    'status'          => 'failed',
                    'error_message'   => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('superadmin.notifications.index')
            ->with('success', "Notification queued for {$sentCount} hospital(s).");
    }
}
