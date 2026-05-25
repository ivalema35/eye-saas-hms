<?php

namespace App\Services\Platform;

use App\Models\Platform\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * InvoiceService — Platform Service
 *
 * Generates A4 PDF invoices for subscription payments using
 * barryvdh/laravel-dompdf.
 *
 * Storage: local disk → storage/app/invoices/{invoiceNumber}.pdf
 * View:    resources/views/pdfs/invoice.blade.php
 */
class InvoiceService
{
    /**
     * Generate a PDF invoice for the given payment and persist it.
     * Returns the storage path relative to the local disk.
     *
     * @throws \Throwable If PDF generation or storage fails.
     */
    public function generate(Payment $payment): string
    {
        $payment->loadMissing('tenant');

        $invoiceNumber = $this->invoiceNumber($payment);

        $pdf = Pdf::loadView('pdfs.invoice', [
            'payment' => $payment,
            'tenant' => $payment->tenant,
            'invoiceNumber' => $invoiceNumber,
        ])->setPaper('a4', 'portrait');

        $path = 'invoices/'.$invoiceNumber.'.pdf';

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Return the deterministic invoice number for a payment.
     * Format: INV-YYYYMM-{id padded to 4 digits}
     * Example: INV-202603-0042
     */
    public function invoiceNumber(Payment $payment): string
    {
        $date = $payment->paid_at ?? $payment->created_at ?? now();

        return sprintf('INV-%s-%04d', $date->format('Ym'), $payment->id);
    }
}
