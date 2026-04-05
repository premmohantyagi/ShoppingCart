<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generateInvoice(Order $order): Invoice
    {
        $order->load('items.product', 'user', 'shippingAddress', 'billingAddress');

        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'status' => 'issued',
        ]);

        $pdf = Pdf::loadView('pdf.invoice', compact('order', 'invoice'));

        $path = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        $invoice->update(['file_path' => $path]);

        return $invoice;
    }

    public function getOrGenerate(Order $order): Invoice
    {
        $invoice = $order->invoices()->latest()->first();

        if (!$invoice) {
            $invoice = $this->generateInvoice($order);
        }

        return $invoice;
    }

    public function downloadPdf(Invoice $invoice)
    {
        if ($invoice->file_path && Storage::disk('public')->exists($invoice->file_path)) {
            return Storage::disk('public')->download($invoice->file_path, $invoice->invoice_number . '.pdf');
        }

        // Regenerate if missing
        $order = $invoice->order->load('items.product', 'user', 'shippingAddress', 'billingAddress');
        $pdf = Pdf::loadView('pdf.invoice', compact('order', 'invoice'));

        return $pdf->download($invoice->invoice_number . '.pdf');
    }
}
