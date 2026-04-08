<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\Subscription;
use App\Services\Platform\SubscriptionService;
use Illuminate\View\View;

/**
 * Subscription Management Controller (SuperAdmin)
 *
 * View & manage all hospital subscriptions, payments.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function index(): View
    {
        $subscriptions = Subscription::with('tenant')->latest()->paginate(25);
        return view('superadmin.subscriptions.index', compact('subscriptions'));
    }
}
