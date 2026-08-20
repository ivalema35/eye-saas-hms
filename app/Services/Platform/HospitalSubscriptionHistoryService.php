<?php

namespace App\Services\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\Payment;
use App\Models\Platform\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HospitalSubscriptionHistoryService
{
    /**
     * @return Collection<int, array{
     *   type: string,
     *   start: ?Carbon,
     *   end: ?Carbon,
     *   days_used: ?int,
     *   days_remaining: ?int,
     *   status: string,
     *   payment_id: ?int,
     *   notes: ?string
     * }>
     */
    public function rowsFor(Tenant $tenant): Collection
    {
        $rows = collect();

        if ($tenant->trial_ends_at) {
            $start = $tenant->created_at?->copy();
            $end = $tenant->trial_ends_at->copy();
            $now = now();

            $daysUsed = $start ? (int) $start->diffInDays($now->lt($end) ? $now : $end) : null;
            $daysRemaining = $now->lt($end) ? (int) $now->diffInDays($end) : 0;

            $rows->push([
                'type' => 'Free Trial',
                'start' => $start,
                'end' => $end,
                'days_used' => $daysUsed,
                'days_remaining' => $daysRemaining,
                'status' => $now->lt($end) ? 'Active' : 'Expired',
                'payment_id' => null,
                'notes' => null,
            ]);
        }

        AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('action', 'hospital.grace.extended')
            ->orderBy('created_at')
            ->get()
            ->each(function (AuditLog $log) use ($rows) {
                $days = (int) ($log->new_values['days'] ?? 0);

                $rows->push([
                    'type' => 'Grace Extension'.($days > 0 ? " (+{$days} days)" : ''),
                    'start' => $log->created_at?->copy(),
                    'end' => null,
                    'days_used' => null,
                    'days_remaining' => $days > 0 ? $days : null,
                    'status' => 'Extended',
                    'payment_id' => null,
                    'notes' => $log->description,
                ]);
            });

        $tenant->subscriptions()
            ->orderByDesc('starts_at')
            ->get()
            ->each(function ($subscription) use ($rows, $tenant) {
                $start = $subscription->starts_at ? Carbon::parse($subscription->starts_at) : null;
                $end = $subscription->ends_at ? Carbon::parse($subscription->ends_at) : null;
                $now = now();

                $daysUsed = ($start && $end)
                    ? (int) $start->diffInDays($now->lt($end) ? $now : $end)
                    : null;
                $daysRemaining = ($end && $now->lt($end)) ? (int) $now->diffInDays($end) : 0;

                $payment = Payment::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('cycle', $subscription->cycle)
                    ->where('status', 'success')
                    ->when($start, fn ($q) => $q->where('paid_at', '>=', $start->copy()->subDay()))
                    ->orderByDesc('paid_at')
                    ->first();

                $status = 'Expired';
                if ($end && $now->lt($end)) {
                    $status = 'Active';
                } elseif ($subscription->status === 'active' && $end && $now->gte($end)) {
                    $status = 'Expired';
                }

                $rows->push([
                    'type' => ucfirst($subscription->cycle).' Plan',
                    'start' => $start,
                    'end' => $end,
                    'days_used' => $daysUsed,
                    'days_remaining' => $daysRemaining,
                    'status' => $status,
                    'payment_id' => $payment?->id,
                    'notes' => null,
                ]);
            });

        return $rows
            ->sortByDesc(fn (array $row) => $row['start']?->getTimestamp() ?? 0)
            ->values();
    }
}
