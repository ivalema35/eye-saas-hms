<?php

/**
 * LandingController.php
 *
 * PURPOSE: Public marketing pages — no auth required.
 *          Landing page aur pricing page handle karta hai.
 *
 * ROUTES:
 *   GET /         → home (index)
 *   GET /pricing  → pricing
 *
 * ACCESSIBLE BY: Anyone (public)
 */

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\PlatformPricingService;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __construct(
        protected PlatformPricingService $pricingService,
    ) {
    }

    /**
     * index() — Main landing / homepage
     */
    public function index(): View
    {
        $pricing = $this->pricingService->basePlans();

        return view('landing.index', compact('pricing'));
    }

    /**
     * pricing() — Standalone pricing page
     */
    public function pricing(): View
    {
        $pricing = $this->pricingService->basePlans();

        return view('landing.pricing', compact('pricing'));
    }
}
