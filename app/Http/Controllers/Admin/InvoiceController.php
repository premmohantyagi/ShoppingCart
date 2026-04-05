<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function generate(Order $order): RedirectResponse
    {
        $this->invoiceService->generateInvoice($order);
        return redirect()->route('admin.orders.show', $order)->with('success', 'Invoice generated.');
    }

    public function download(Invoice $invoice)
    {
        return $this->invoiceService->downloadPdf($invoice);
    }
}
