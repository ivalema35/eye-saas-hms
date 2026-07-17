<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformSubscriptionApiController extends Controller
{
    // GET /api/v1/super/subscriptions
    public function index(Request $request): JsonResponse
    {
        $query = Subscription::with('tenant:id,name,slug')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $subscriptions = $query->paginate(25);

        $totalCount   = Subscription::count();
        $activeCount  = Subscription::where('status', 'active')->count();
        $expiredCount = Subscription::where('status', 'expired')->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'stats' => [
                    'total'   => $totalCount,
                    'active'  => $activeCount,
                    'expired' => $expiredCount,
                ],
                'subscriptions' => $subscriptions->map(fn($s) => $this->formatSubscription($s))->values(),
                'meta'          => [
                    'total'        => $subscriptions->total(),
                    'last_page'    => $subscriptions->lastPage(),
                    'current_page' => $subscriptions->currentPage(),
                ],
            ],
        ]);
    }

    private function formatSubscription(Subscription $s): array
    {
        return [
            'id'          => $s->id,
            'tenant_id'   => $s->tenant_id,
            'tenant_name' => $s->tenant?->name,
            'tenant_slug' => $s->tenant?->slug,
            'cycle'       => $s->cycle,
            'status'      => $s->status,
            'price'       => (float) $s->price,
            'starts_at'   => $s->starts_at?->toIso8601String(),
            'ends_at'     => $s->ends_at?->toIso8601String(),
            'created_at'  => $s->created_at?->toIso8601String(),
        ];
    }
}
