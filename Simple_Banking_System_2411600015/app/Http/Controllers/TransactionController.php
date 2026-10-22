<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidTransactionException;
use App\Exceptions\MinimumBalanceException;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * List transactions. Administrators see all of them,
     * everybody else only sees transactions of their own accounts.
     */
    public function index(Request $request)
    {
        $transactions = Transaction::visibleTo($request->user())
            ->with('account')
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);

        return view('transactions.index', compact('transactions'));
    }

    /**
     * The form only lists the logged-in user's own accounts.
     */
    public function create(Request $request)
    {
        $accounts = Account::where('user_id', $request->user()->id)
            ->orderBy('account_number')
            ->get();

        return view('transactions.create', compact('accounts'));
    }

    /**
     * Save a deposit or withdrawal.
     *
     * NOTE: this method never touches the balance. It only creates the
     * Transaction row; the AccountObserver checks the rules and updates the
     * balance inside the same DB::transaction().
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'type'       => ['required', Rule::in([Transaction::TYPE_DEPOSIT, Transaction::TYPE_WITHDRAWAL])],
            'amount'     => ['required', 'numeric', 'min:1', 'max:1000000000', 'decimal:0,2'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        // Authorization: you can only use your own account (even if you are an admin)
        $account = Account::findOrFail($data['account_id']);
        abort_unless((int) $account->user_id === (int) $request->user()->id, 403, 'This is not your account.');

        try {
            // The observer runs inside this transaction. If it throws,
            // the new row AND the balance change are both rolled back.
            DB::transaction(function () use ($data) {
                Transaction::create([
                    'account_id'       => $data['account_id'],
                    'type'             => $data['type'],
                    'amount'           => $data['amount'],
                    'reference_number' => 'TXN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                    'notes'            => $data['notes'] ?? null,
                ]);
            });
        } catch (InsufficientFundsException|MinimumBalanceException|InvalidTransactionException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        $newBalance = number_format((float) $account->fresh()->balance, 2);

        return redirect()
            ->route('transactions.index')
            ->with('success', ucfirst($data['type']) . ' of ' . number_format((float) $data['amount'], 2)
                . ' was saved. New balance: ' . $newBalance . '.');
    }
}
