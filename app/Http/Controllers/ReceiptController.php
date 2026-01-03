<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt): View
    {
        $receipt->load('lineItems.product');

        return view('receipts.show', compact('receipt'));
    }
}
