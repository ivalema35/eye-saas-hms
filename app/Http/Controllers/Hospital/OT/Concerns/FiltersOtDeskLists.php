<?php

namespace App\Http\Controllers\Hospital\OT\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared Queue / History (+ date range) parsing for OT desk dashboards.
 *
 * Convention:
 * - queue (aliases: today, pending) — work still at this desk
 * - history (aliases: all, completed) — stage already finished; date-filtered
 * - refunds — accountant only
 */
trait FiltersOtDeskLists
{
    /**
     * @param  list<string>  $allowed
     */
    protected function resolveOtDeskFilter(Request $request, array $allowed = ['queue', 'history'], string $default = 'queue'): string
    {
        $raw = strtolower((string) $request->query('filter', $default));

        $normalized = match ($raw) {
            'today', 'pending' => 'queue',
            'all', 'completed' => 'history',
            default => $raw,
        };

        if (! in_array($normalized, $allowed, true)) {
            return $default;
        }

        return $normalized;
    }

    /**
     * History date range. Defaults to today when history is active and dates omitted.
     *
     * @return array{0: string, 1: string} [from Y-m-d, to Y-m-d]
     */
    protected function resolveOtDeskDateRange(Request $request, bool $forHistory): array
    {
        $today = now()->toDateString();

        if (! $forHistory) {
            return [$today, $today];
        }

        $from = (string) ($request->query('from_date') ?: $request->query('from') ?: $today);
        $to = (string) ($request->query('to_date') ?: $request->query('to') ?: $from);

        try {
            $fromCarbon = Carbon::parse($from)->startOfDay();
            $toCarbon = Carbon::parse($to)->endOfDay();
        } catch (\Throwable) {
            return [$today, $today];
        }

        if ($toCarbon->lt($fromCarbon)) {
            [$fromCarbon, $toCarbon] = [$toCarbon->copy()->startOfDay(), $fromCarbon->copy()->endOfDay()];
        }

        return [$fromCarbon->toDateString(), $toCarbon->toDateString()];
    }

    /**
     * Prefer surgery_date; fall back to updated_at when surgery_date is null.
     */
    protected function applyOtDeskDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->where(function (Builder $outer) use ($from, $to) {
            $outer->where(function (Builder $q) use ($from, $to) {
                $q->whereNotNull('surgery_date')
                    ->whereDate('surgery_date', '>=', $from)
                    ->whereDate('surgery_date', '<=', $to);
            })->orWhere(function (Builder $q) use ($from, $to) {
                $q->whereNull('surgery_date')
                    ->whereDate('updated_at', '>=', $from)
                    ->whereDate('updated_at', '<=', $to);
            });
        });
    }
}
