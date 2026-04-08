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
use Illuminate\View\View;

class LandingController extends Controller
{
    /**
     * index() — Main landing / homepage
     */
    public function index(): View
    {
        $pricing = [
            'monthly'   => ['price' => 999, 'original' => 999, 'label' => 'Monthly'],
            'quarterly' => ['price' => 2427, 'original' => 2697, 'label' => 'Quarterly', 'save' => 270],
            'yearly'    => ['price' => 9590, 'original' => 11988, 'label' => 'Yearly', 'save' => 2398],
        ];

        return view('landing.index', compact('pricing'));
    }

    /**
     * pricing() — Standalone pricing page
     */
    public function pricing(): View
    {
        $pricing = [
            'monthly'   => ['price' => 999, 'original' => 999, 'label' => 'Monthly'],
            'quarterly' => ['price' => 2427, 'original' => 2697, 'label' => 'Quarterly', 'save' => 270],
            'yearly'    => ['price' => 9590, 'original' => 11988, 'label' => 'Yearly', 'save' => 2398],
        ];

        return view('landing.pricing', compact('pricing'));
    }
}
