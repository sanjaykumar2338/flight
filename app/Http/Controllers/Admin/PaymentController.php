<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::query()
            ->with('booking')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('provider'), fn ($query) => $query->where('provider', $request->input('provider')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', [
            'transactions' => $transactions,
            'filters' => $request->only(['status', 'provider', 'date_from', 'date_to']),
        ]);
    }

    public function show(Transaction $payment)
    {
        $payment->load('booking');

        return view('admin.payments.show', [
            'payment' => $payment,
        ]);
    }
}
