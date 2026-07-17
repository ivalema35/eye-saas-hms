<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\AuditLog;
use App\Models\Platform\Payment;
use App\Models\Platform\Tenant;
use App\Services\Platform\InvoiceService;
use App\Services\Platform\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformPaymentApiController extends Controller
{
    public function __construct(
        protected TenantService  $tenantService,
        protected InvoiceService $invoiceService,
    ) {}

    // GET /api/v1/super/payments
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with('tenant:id,name,slug')->latest('paid_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($method = $request->query('method')) {
            $query->where('method', $method);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('paid_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('paid_at', '<=', $to);
        }

        $payments = $query->paginate(25);

        $totalRevenue = Payment::where('status', 'success')->sum('amount');
        $thisMonth    = Payment::where('status', 'success')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
        $pendingCount = Payment::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'stats' => [
                    'total_revenue' => (float) $totalRevenue,
                    'this_month'    => (float) $thisMonth,
                    'pending_count' => $pendingCount,
                ],
                'payments' => $payments->map(fn($p) => $this->formatPayment($p))->values(),
                'meta'     => [
                    'total'        => $payments->total(),
                    'last_page'    => $payments->lastPage(),
                    'current_page' => $payments->currentPage(),
                ],
            ],
        ]);
    }

    // GET /api/v1/super/payments/tenant-options
    // Non-paginated list of non-suspended tenants for the offline payment dropdown
    public function tenantOptions(): JsonResponse
    {
        $tenants = Tenant::whereNotIn('status', ['suspended'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status']);

        return response()->json([
            'success' => true,
            'data'    => $tenants->map(fn($t) => [
                'id'     => $t->id,
                'name'   => $t->name,
                'slug'   => $t->slug,
                'status' => $t->status,
            ])->values(),
        ]);
    }

    // POST /api/v1/super/payments/offline
    public function storeOffline(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'      => ['required', 'exists:tenants,id'],
            'amount'         => ['required', 'numeric', 'min:1', 'max:999999'],
            'cycle'          => ['required', 'in:monthly,quarterly,yearly'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $tenant  = Tenant::findOrFail($validated['tenant_id']);
        $payment = null;

        DB::transaction(function () use ($validated, $tenant, &$payment): void {
            $payment = Payment::create([
                'tenant_id'      => $tenant->id,
                'amount'         => $validated['amount'],
                'cycle'          => $validated['cycle'],
                'method'         => 'offline',
                'gateway'        => null,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'status'         => 'success',
                'paid_at'        => now(),
                'notes'          => $validated['notes'] ?? null,
            ]);

            $this->tenantService->activate($tenant);
        });

        try {
            $invoicePath = $this->invoiceService->generate($payment);
            $payment->update(['invoice_path' => $invoicePath]);
        } catch (\Throwable $e) {
            Log::warning("Invoice PDF generation failed for payment #{$payment->id}: " . $e->getMessage());
        }

        AuditLog::create([
            'admin_id'    => $request->user()->id,
            'tenant_id'   => $tenant->id,
            'action'      => 'payment.offline.recorded',
            'description' => "Offline payment of ₹{$validated['amount']} recorded for '{$tenant->name}'",
            'ip_address'  => $request->ip(),
            'new_values'  => ['amount' => $validated['amount'], 'cycle' => $validated['cycle']],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Offline payment of ₹{$validated['amount']} recorded for '{$tenant->name}'. Hospital activated.",
            'data'    => $this->formatPayment($payment->load('tenant')),
        ], 201);
    }

    // GET /api/v1/super/payments/{id}/invoice
    public function downloadInvoice(int $id): StreamedResponse|JsonResponse
    {
        $payment = Payment::findOrFail($id);

        if (! $payment->invoice_path || ! Storage::disk('local')->exists($payment->invoice_path)) {
            try {
                $path = $this->invoiceService->generate($payment);
                $payment->update(['invoice_path' => $path]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => 'Invoice generation failed.'], 500);
            }
        }

        $filename = 'invoice-' . $this->invoiceService->invoiceNumber($payment) . '.pdf';

        return Storage::disk('local')->download($payment->invoice_path, $filename);
    }

    private function formatPayment(Payment $p): array
    {
        return [
            'id'             => $p->id,
            'tenant_id'      => $p->tenant_id,
            'tenant_name'    => $p->tenant?->name,
            'tenant_slug'    => $p->tenant?->slug,
            'amount'         => (float) $p->amount,
            'cycle'          => $p->cycle,
            'method'         => $p->method,
            'status'         => $p->status,
            'transaction_id' => $p->transaction_id,
            'notes'          => $p->notes,
            'invoice_path'   => $p->invoice_path,
            'paid_at'        => $p->paid_at?->toIso8601String(),
            'created_at'     => $p->created_at?->toIso8601String(),
        ];
    }
}
