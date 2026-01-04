<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt): View
    {
        $receipt->load('lineItems.product');

        $unmatchedBonusCount = $receipt->pendingUnmatchedBonuses()->count();
        $totalDiscount = $receipt->total_discount;

        return view('receipts.show', compact('receipt', 'unmatchedBonusCount', 'totalDiscount'));
    }
}
