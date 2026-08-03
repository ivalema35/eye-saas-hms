<?php

namespace App\Http\Controllers\Hospital\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * OT Assistant home drill-down — bookings assigned via Ward (ot_assistant_id).
 */
class AssistantOtListController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        $user = Auth::guard('hospital_user')->user();
        abort_unless($user, 403);

        $assistantId = (int) $user->id;
        // Admin / other roles can pass assistant_id; assistants always see own queue.
        if ($user->role?->slug !== 'ot_assistant' && $request->filled('assistant_id')) {
            $assistantId = (int) $request->input('assistant_id');
        }

        [$startDate, $endDate] = $this->resolvedDates($request);

        $query = OtBooking::query()
            ->with([
                'patient:id,first_name,middle_name,last_name,contact_no,age',
                'otDoctor:id,name',
                'otAssistant:id,name',
                'payments',
            ])
            ->where('ot_assistant_id', $assistantId);

        if ($startDate) {
            $query->whereDate('surgery_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('surgery_date', '<=', $endDate);
        }

        $bookings = $query
            ->orderByDesc('surgery_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('hospital.dashboard.assistant_ot_list', [
            'slug' => $slug,
            'bookings' => $bookings,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'assistantName' => $user->role?->slug === 'ot_assistant' ? $user->name : null,
        ]);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolvedDates(Request $request): array
    {
        $start = $request->filled('start_date') ? (string) $request->input('start_date') : null;
        $end = $request->filled('end_date') ? (string) $request->input('end_date') : $start;

        if ($start && $end && $end < $start) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
