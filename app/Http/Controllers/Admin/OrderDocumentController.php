<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\View\View;

/** Printable invoice / packing slip (use the browser's "Print → Save as PDF"). */
class OrderDocumentController
{
    public function __invoke(Order $order, string $document): View
    {
        $order->load('items');

        return view('admin.order-document', [
            'order' => $order,
            'isInvoice' => $document === 'invoice',
        ]);
    }
}
