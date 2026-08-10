<?php

namespace App\Services\Hospital;

use App\Models\Hospital\OT\OtPayment;
use App\Models\Hospital\OT\OtRefund;
use App\Models\Hospital\Patient;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Unified hospital collection / revenue (web).
 *
 * Formula:
 *   total = OPD case_fee + OT payments − OT refunds
 *
 * OT refunds never reduce OPD case_fee.
 */
class HospitalCollectionService
{
    /**
     * @return array{
     *     opd: float,
     *     ot_collected: float,
     *     ot_refunded: float,
     *     ot_net: float,
     *     total: float
     * }
     */
    public function summaryForDateRange(string $startDate, string $endDate): array
    {
        $opd = (float) Patient::query()
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->sum('case_fee');

        $otCollected = (float) OtPayment::query()
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->sum('package_amount');

        $otRefunded = (float) OtRefund::query()
            ->whereDate('refunded_at', '>=', $startDate)
            ->whereDate('refunded_at', '<=', $endDate)
            ->sum('amount');

        $otNet = round($otCollected - $otRefunded, 2);
        $total = round($opd + $otNet, 2);

        return [
            'opd' => round($opd, 2),
            'ot_collected' => round($otCollected, 2),
            'ot_refunded' => round($otRefunded, 2),
            'ot_net' => $otNet,
            'total' => $total,
        ];
    }

    /**
     * @return array{
     *     opd: float,
     *     ot_collected: float,
     *     ot_refunded: float,
     *     ot_net: float,
     *     total: float
     * }
     */
    public function summaryForDay(CarbonInterface|string $day): array
    {
        $date = Carbon::parse($day)->toDateString();

        return $this->summaryForDateRange($date, $date);
    }

    /**
     * @return array{
     *     opd: float,
     *     ot_collected: float,
     *     ot_refunded: float,
     *     ot_net: float,
     *     total: float
     * }
     */
    public function summaryForCalendarMonth(CarbonInterface|null $when = null): array
    {
        $when = $when ? Carbon::parse($when) : now();

        return $this->summaryForDateRange(
            $when->copy()->startOfMonth()->toDateString(),
            $when->copy()->endOfMonth()->toDateString()
        );
    }

    /**
     * @return array{
     *     opd: float,
     *     ot_collected: float,
     *     ot_refunded: float,
     *     ot_net: float,
     *     total: float
     * }
     */
    public function summaryForCalendarYear(CarbonInterface|null $when = null): array
    {
        $when = $when ? Carbon::parse($when) : now();

        return $this->summaryForDateRange(
            $when->copy()->startOfYear()->toDateString(),
            $when->copy()->endOfYear()->toDateString()
        );
    }
}
