<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Platform\Tenant;
use App\Http\Controllers\Api\OtDischargeApiController;

$tenant = Tenant::first();
if (! $tenant) {
    fwrite(STDERR, "No tenant found in DB.\n");
    exit(1);
}
app()->instance('tenant', $tenant);

$patient = (object) [
    'full_name' => 'Ramanbhai Manubhai Patel',
    'city_name' => 'Ahmedabad',
    'location_label' => 'Ahmedabad',
];

$booking = (object) [
    'id' => 12345,
    'patient' => $patient,
    'discharged_at' => null,
];

$invoice = (object) [
    'invoice_number' => 'INV-000123',
    'created_at' => now(),
    'net_amount' => 0,
    'total_amount' => 0,
    'line_items' => null,
];

$lineItems = [
    ['head' => 'Surgery Charges', 'amount' => 25000],
    ['head' => 'Room Rent', 'amount' => 3200],
    ['head' => 'Medicine', 'amount' => 1500],
    ['head' => 'Consultation', 'amount' => 800],
];

$controller = new OtDischargeApiController();
$ref = new ReflectionMethod(OtDischargeApiController::class, 'domPdfSafeHtml');
$ref->setAccessible(true);
$html = $ref->invoke($controller, 'hospital.ot.billing.summary_bill_print', compact('booking', 'invoice', 'lineItems'));

file_put_contents(__DIR__ . '/pdf_repro_tmp.html', $html);

$pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a5', 'portrait');
file_put_contents(__DIR__ . '/pdf_repro_tmp.pdf', $pdf->output());

echo "Wrote pdf_repro_tmp.html and pdf_repro_tmp.pdf\n";
