<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\NotificationMail;
use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformNotification;
use App\Models\Platform\Tenant;
use App\Services\Platform\MailConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PlatformNotificationApiController extends Controller
{
    // GET /api/v1/super/notifications
    public function index(): JsonResponse
    {
        $tenants = Tenant::whereNotNull('admin_email')
            ->whereNotIn('status', ['suspended'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'admin_email', 'status']);

        $history = PlatformNotification::with('tenant:id,name,slug')
            ->where('type', 'broadcast')
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'history' => $history->map(fn($n) => $this->formatNotification($n))->values(),
                'tenants' => $tenants->map(fn($t) => [
                    'id'          => $t->id,
                    'name'        => $t->name,
                    'slug'        => $t->slug,
                    'admin_email' => $t->admin_email,
                    'status'      => $t->status,
                ])->values(),
            ],
        ]);
    }

    // POST /api/v1/super/notifications/send
    public function send(Request $request): JsonResponse
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

        MailConfigService::apply();

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

        AuditLog::create([
            'admin_id'    => $request->user()->id,
            'tenant_id'   => null,
            'action'      => 'notification.broadcast.sent',
            'description' => "Broadcast notification '{$validated['subject']}' sent to {$sentCount} hospital(s)",
            'ip_address'  => $request->ip(),
            'new_values'  => ['subject' => $validated['subject'], 'recipient' => $validated['recipient'], 'count' => $sentCount],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Notification queued for {$sentCount} hospital(s).",
        ]);
    }

    private function formatNotification(PlatformNotification $n): array
    {
        return [
            'id'              => $n->id,
            'tenant_id'       => $n->tenant_id,
            'tenant_name'     => $n->tenant?->name,
            'tenant_slug'     => $n->tenant?->slug,
            'subject'         => $n->subject,
            'recipient_email' => $n->recipient_email,
            'status'          => $n->status,
            'error_message'   => $n->error_message,
            'sent_at'         => $n->sent_at?->toIso8601String(),
            'created_at'      => $n->created_at?->toIso8601String(),
        ];
    }
}
