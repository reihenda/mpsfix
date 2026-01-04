<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FinancialAccountController extends Controller
{
    /**
     * Display a listing of the accounts.
     */
    public function index(Request $request)
    {
        $query = FinancialAccount::query();
        
        // Filter by account type if specified
        if ($request->has('type') && in_array($request->type, ['kas', 'bank'])) {
            $query->ofType($request->type);
        }
        
        $accounts = $query->orderBy('account_code')->paginate(10);
        
        return view('keuangan.accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new account.
     */
    public function create()
    {
        return view('keuangan.accounts.create');
    }

    /**
     * Store a newly created account in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_code' => 'required|string|max:20|unique:financial_accounts',
            'account_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'account_type' => 'required|in:kas,bank',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        FinancialAccount::create($request->all());

        return redirect()->route('keuangan.accounts.index')
            ->with('success', 'Akun keuangan berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(FinancialAccount $account)
    {
        return view('keuangan.accounts.edit', compact('account'));
    }

    /**
     * Update the specified account in storage.
     */
    public function update(Request $request, FinancialAccount $account)
    {
        $validator = Validator::make($request->all(), [
            'account_code' => 'required|string|max:20|unique:financial_accounts,account_code,' . $account->id,
            'account_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'account_type' => 'required|in:kas,bank',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Set is_active to false if not provided in the request
        if (!$request->has('is_active')) {
            $request->merge(['is_active' => false]);
        }

        $account->update($request->all());

        return redirect()->route('keuangan.accounts.index')
            ->with('success', 'Akun keuangan berhasil diperbarui.');
    }

    /**
     * Remove the specified account from storage.
     */
    public function destroy(FinancialAccount $account)
    {
        // Check if account is used in any transactions
        $kasTransactionsCount = $account->kasTransactions()->count();
        $bankTransactionsCount = $account->bankTransactions()->count();
        
        if ($kasTransactionsCount > 0 || $bankTransactionsCount > 0) {
            return redirect()->back()
                ->with('error', 'Akun ini tidak dapat dihapus karena sudah digunakan dalam transaksi.');
        }
        
        $account->delete();
        
        return redirect()->route('keuangan.accounts.index')
            ->with('success', 'Akun keuangan berhasil dihapus.');
    }
}
