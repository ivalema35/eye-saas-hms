<?php

/**
 * LensInventoryService.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 7 (Inventory Module). Decrements
 *          lens_inventory.available_stock exactly once when a lens detail
 *          transitions into "implanted" — never on re-saves of an already
 *          implanted record, and never below zero.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §7.
 */

namespace App\Services\Hospital;

use App\Models\Hospital\OT\LensInventory;

class LensInventoryService
{
    /**
     * @param  bool  $wasImplanted  Whether the lens detail was already marked implanted
     *                              BEFORE this save (i.e. the pre-update DB state).
     * @param  bool  $isImplanted  The state being saved now.
     */
    public function handleImplantTransition(?int $lensInventoryId, bool $wasImplanted, bool $isImplanted): void
    {
        if (! $lensInventoryId) {
            return;
        }

        // Only decrement on the false -> true transition, so editing an already
        // implanted record (e.g. fixing a typo) never double-deducts stock.
        if ($isImplanted && ! $wasImplanted) {
            LensInventory::query()
                ->where('id', $lensInventoryId)
                ->where('available_stock', '>', 0)
                ->decrement('available_stock');
        }
    }
}
