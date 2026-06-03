<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\AuditLog;
use App\Models\Platform\Payment;
use App\Models\Platform\Tenant;
use App\Services\Platform\InvoiceService;
use App\Services\Platform\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected TenantService $tenantService,
        protected InvoiceService $invoiceService
    ) {}

    public function index(): View
    {
        $query = Payment::with('tenant')->latest('paid_at');

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($method = request('method')) {
            $query->where('method', $method);
        }

        if ($from = request('from')) {
            $query->whereDate('paid_at', '>=', $from);
        }

        if ($to = request('to')) {
            $query->whereDate('paid_at', '<=', $to);
        }

        $payments = $query->paginate(25)->appends(request()->query());

        $totalRevenue = Payment::where('status', 'success')->sum('amount');
        $thisMonth = Payment::where('status', 'success')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
        $pendingCount = Payment::where('status', 'pending')->count();

        $tenants = Tenant::whereNotIn('status', ['suspended'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status']);

        return view('superadmin.payments.index', compact(
            'payments',
            'totalRevenue',
            'thisMonth',
            'pendingCount',
            'tenants'
        ));
    }

    public function storeOffline(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999'],
            'cycle' => ['required', 'in:monthly,quarterly,yearly'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $payment = null;

        DB::transaction(function () use ($validated, $tenant, &$payment): void {
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'amount' => $validated['amount'],
                'cycle' => $validated['cycle'],
                'method' => 'offline',
                'gateway' => null,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'status' => 'success',
                'paid_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->tenantService->activate($tenant);
        });

        // Generate PDF invoice outside the transaction so a PDF failure doesn't roll back the payment
        try {
            $invoicePath = $this->invoiceService->generate($payment);
            $payment->update(['invoice_path' => $invoicePath]);
        } catch (\Throwable $e) {
            Log::warning("Invoice PDF generation failed for payment #{$payment->id}: ".$e->getMessage());
        }

        AuditLog::create([
            'admin_id' => auth('superadmin')->id(),
            'tenant_id' => $tenant->id,
            'action' => 'payment.offline.recorded',
            'description' => "Offline payment of ₹{$validated['amount']} recorded for '{$tenant->name}'",
            'ip_address' => request()->ip(),
            'new_values' => ['amount' => $validated['amount'], 'cycle' => $validated['cycle']],
        ]);

        return back()->with(
            'success',
            "Offline payment of ₹{$validated['amount']} recorded for '{$tenant->name}'. Hospital activated."
        );
    }

    /**
     * Download or regenerate the PDF invoice for a payment.
     */
    public function downloadInvoice(Payment $payment): StreamedResponse
    {
        if (! $payment->invoice_path || ! Storage::disk('local')->exists($payment->invoice_path)) {
            $path = $this->invoiceService->generate($payment);
            $payment->update(['invoice_path' => $path]);
        }

        $filename = 'invoice-'.$this->invoiceService->invoiceNumber($payment).'.pdf';

        return Storage::disk('local')->download($payment->invoice_path, $filename);
    }
}
