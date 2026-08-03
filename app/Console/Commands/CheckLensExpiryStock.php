<?php

namespace App\Console\Commands;

use App\Models\Hospital\OT\LensInventory;
use App\Models\Platform\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('ot:check-lens-stock {--tenant_id= : Limit to one tenant} {--low=5 : Low-stock threshold} {--days=30 : Expiry warning window in days}')]
#[Description('Report OT lens inventory low-stock and near-expiry items')]
class CheckLensExpiryStock extends Command
{
    public function handle(): int
    {
        $tenantId = $this->option('tenant_id');
        $lowThreshold = max(0, (int) $this->option('low'));
        $days = max(1, (int) $this->option('days'));
        $expiryBefore = now()->addDays($days)->toDateString();

        $tenants = Tenant::query()
            ->when($tenantId, fn ($q) => $q->whereKey((int) $tenantId))
            ->orderBy('id')
            ->get(['id', 'name', 'slug']);

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return self::SUCCESS;
        }

        $totalAlerts = 0;

        foreach ($tenants as $tenant) {
            $lowStock = LensInventory::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->where('available_stock', '<=', $lowThreshold)
                ->orderBy('available_stock')
                ->get(['id', 'lens_code', 'lens_name', 'available_stock', 'expiry_date']);

            $nearExpiry = LensInventory::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', $expiryBefore)
                ->orderBy('expiry_date')
                ->get(['id', 'lens_code', 'lens_name', 'available_stock', 'expiry_date']);

            if ($lowStock->isEmpty() && $nearExpiry->isEmpty()) {
                continue;
            }

            $totalAlerts += $lowStock->count() + $nearExpiry->count();
            $this->line("Tenant #{$tenant->id} ({$tenant->slug})");

            foreach ($lowStock as $row) {
                $this->warn("  LOW STOCK  {$row->lens_code} {$row->lens_name} — qty {$row->available_stock}");
            }
            foreach ($nearExpiry as $row) {
                $expiry = optional($row->expiry_date)->format('d M Y') ?? '-';
                $this->warn("  EXPIRING   {$row->lens_code} {$row->lens_name} — {$expiry}");
            }

            Log::warning('OT lens stock alert', [
                'tenant_id' => $tenant->id,
                'low_stock_count' => $lowStock->count(),
                'near_expiry_count' => $nearExpiry->count(),
            ]);
        }

        if ($totalAlerts === 0) {
            $this->info('No low-stock or near-expiry lens items found.');
        } else {
            $this->info("Total alert rows: {$totalAlerts}");
        }

        return self::SUCCESS;
    }
}
