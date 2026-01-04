<?php

namespace App\Http\Controllers;

use App\Models\KasTransaction;
use App\Models\FinancialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KasController extends Controller
{
    /**
     * Display a listing of kas transactions.
     */
    public function index(Request $request)
    {
        // Default to current month if not provided
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Get monthly summary data
        $monthlySummary = $this->getMonthlySummary($month, $year);

        // Get the running balance at the start of the month
        $previousBalance = $this->getBalanceBefore($month, $year);

        // Calculate overall account summary
        $totalSummary = $this->getTotalSummary();

        // Get transactions for the selected month and year
        $transactions = KasTransaction::with('account')
            ->forMonthYear($month, $year)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->paginate(15);

        // Get available years and months for the dropdown
        $availableYears = KasTransaction::select(DB::raw('DISTINCT year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [Carbon::now()->year];
        }

        return view('keuangan.kas.index', compact(
            'transactions',
            'month',
            'year',
            'monthlySummary',
            'previousBalance',
            'totalSummary',
            'availableYears'
        ));
    }

    /**
     * Show the form for creating a new kas transaction.
     */
    public function create()
    {
        // Get only active accounts for Kas
        $accounts = FinancialAccount::active()->ofType('kas')->orderBy('account_name')->get();

        // Generate a new voucher number
        $voucherNumber = KasTransaction::generateVoucherNumber();

        return view('keuangan.kas.create', compact('accounts', 'voucherNumber'));
    }

    /**
     * Store a newly created kas transaction in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'voucher_number' => 'required|string|unique:kas_transactions',
            'account_id' => 'required|exists:financial_accounts,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'credit' => 'nullable|numeric|min:0',
            'debit' => 'nullable|numeric|min:0',
        ]);

        // Ensure at least one of credit or debit is provided
        if ((float)$request->credit == 0 && (float)$request->debit == 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['message' => 'Minimal salah satu dari kredit atau debit harus diisi.']);
        }

        // Begin database transaction
        DB::beginTransaction();

        try {
            // Parse the transaction date
            $transactionDate = Carbon::parse($request->transaction_date);
            $year = $transactionDate->year;
            $month = $transactionDate->month;

            // Get the previous transaction to calculate the new balance
            $previousTransaction = KasTransaction::where(function ($query) use ($transactionDate) {
                $query->where('transaction_date', '<', $transactionDate)
                    ->orWhere(function ($q) use ($transactionDate) {
                        $q->where('transaction_date', $transactionDate)
                            ->where('id', '<', DB::raw('(SELECT COALESCE(MAX(id), 0) FROM kas_transactions)'));
                    });
            })
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $previousBalance = $previousTransaction ? $previousTransaction->balance : 0;

            // Calculate the new balance
            $credit = (float)$request->credit;
            $debit = (float)$request->debit;
            $newBalance = $previousBalance + $credit - $debit;

            // Create the transaction
            $transaction = new KasTransaction([
                'voucher_number' => $request->voucher_number,
                'account_id' => $request->account_id,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'credit' => $credit,
                'debit' => $debit,
                'balance' => $newBalance,
                'year' => $year,
                'month' => $month,
            ]);

            $transaction->save();

            // Update balances for all future transactions
            $this->updateFutureBalances($transaction);

            DB::commit();

            return redirect()->route('keuangan.kas.index', ['month' => $month, 'year' => $year])
                ->with('success', 'Transaksi kas berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified kas transaction.
     */
    public function edit(KasTransaction $transaction)
    {
        // Get only active accounts for Kas
        $accounts = FinancialAccount::active()->ofType('kas')->orderBy('account_name')->get();

        // Format numbers for display with thousands separator
        $transaction->credit_formatted = number_format($transaction->credit, 0, ',', '.');
        $transaction->debit_formatted = number_format($transaction->debit, 0, ',', '.');

        return view('keuangan.kas.edit', compact('transaction', 'accounts'));
    }

    /**
     * Update the specified kas transaction in storage.
     */
    public function update(Request $request, KasTransaction $transaction)
    {
        $request->validate([
            'voucher_number' => 'required|string|unique:kas_transactions,voucher_number,' . $transaction->id,
            'account_id' => 'required|exists:financial_accounts,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'credit' => 'nullable|numeric|min:0',
            'debit' => 'nullable|numeric|min:0',
        ]);

        // Convert formatted numbers to actual numbers
        $credit = (float)str_replace(['.', ','], ['', '.'], $request->credit);
        $debit = (float)str_replace(['.', ','], ['', '.'], $request->debit);

        // Ensure at least one of credit or debit is provided
        if ($credit == 0 && $debit == 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['message' => 'Minimal salah satu dari kredit atau debit harus diisi.']);
        }

        // Begin database transaction
        DB::beginTransaction();

        try {
            // Parse the transaction date
            $oldTransactionDate = Carbon::parse($transaction->transaction_date);
            $newTransactionDate = Carbon::parse($request->transaction_date);
            $year = $newTransactionDate->year;
            $month = $newTransactionDate->month;

            // Get the previous transaction to calculate the new balance
            $previousTransaction = KasTransaction::where(function ($query) use ($transaction, $newTransactionDate) {
                $query->where('transaction_date', '<', $newTransactionDate)
                    ->orWhere(function ($q) use ($transaction, $newTransactionDate) {
                        $q->where('transaction_date', $newTransactionDate)
                            ->where('id', '<', $transaction->id);
                    });
            })
                ->where('id', '!=', $transaction->id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $previousBalance = $previousTransaction ? $previousTransaction->balance : 0;

            // Calculate the new balance
            $newBalance = $previousBalance + $credit - $debit;

            // Update the transaction
            $transaction->update([
                'voucher_number' => $request->voucher_number,
                'account_id' => $request->account_id,
                'transaction_date' => $request->transaction_date,
                'description' => $request->description,
                'credit' => $credit,
                'debit' => $debit,
                'balance' => $newBalance,
                'year' => $year,
                'month' => $month,
            ]);

            // Update balances for all future transactions
            $this->updateFutureBalances($transaction);

            DB::commit();

            return redirect()->route('keuangan.kas.index', ['month' => $month, 'year' => $year])
                ->with('success', 'Transaksi kas berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified kas transaction from storage.
     */
    public function destroy(KasTransaction $transaction)
    {
        // Begin database transaction
        DB::beginTransaction();

        try {
            $transactionDate = $transaction->transaction_date;
            $month = $transaction->month;
            $year = $transaction->year;

            // Delete the transaction
            $transaction->delete();

            // Get all later transactions to update balances
            $laterTransactions = KasTransaction::where(function ($query) use ($transaction, $transactionDate) {
                $query->where('transaction_date', '>', $transactionDate)
                    ->orWhere(function ($q) use ($transaction, $transactionDate) {
                        $q->where('transaction_date', $transactionDate)
                            ->where('id', '>', $transaction->id);
                    });
            })
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            if ($laterTransactions->count() > 0) {
                // Get the balance before the first affected transaction
                $previousTransaction = KasTransaction::where(function ($query) use ($laterTransactions) {
                    $query->where('transaction_date', '<', $laterTransactions->first()->transaction_date)
                        ->orWhere(function ($q) use ($laterTransactions) {
                            $q->where('transaction_date', $laterTransactions->first()->transaction_date)
                                ->where('id', '<', $laterTransactions->first()->id);
                        });
                })
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $runningBalance = $previousTransaction ? $previousTransaction->balance : 0;

                // Update each transaction's balance
                foreach ($laterTransactions as $laterTransaction) {
                    $runningBalance = $runningBalance + $laterTransaction->credit - $laterTransaction->debit;
                    $laterTransaction->balance = $runningBalance;
                    $laterTransaction->save();
                }
            }

            DB::commit();

            return redirect()->route('keuangan.kas.index', ['month' => $month, 'year' => $year])
                ->with('success', 'Transaksi kas berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }
    public function recalculateAllBalances()
    {
        try {
            DB::transaction(function () {
                $runningBalance = 0.00;

                // Proses semua transaksi secara berurutan
                KasTransaction::orderBy('transaction_date')->orderBy('id')
                    ->chunkById(500, function ($transactions) use (&$runningBalance) {
                        foreach ($transactions as $transaction) {
                            $runningBalance += (float)$transaction->credit - (float)$transaction->debit;

                            // Langsung update balance jika berbeda, untuk memastikan konsistensi
                            if ((float)$transaction->balance != $runningBalance) {
                                $transaction->balance = $runningBalance;
                                $transaction->saveQuietly(); // save() tanpa menjalankan event
                            }
                        }
                    });
            });

            return redirect()->route('keuangan.kas.index')
                ->with('success', 'Semua saldo transaksi berhasil dihitung ulang dan diperbaiki.');
        } catch (\Exception $e) {
            return redirect()->route('keuangan.kas.index')
                ->with('error', 'Terjadi kesalahan saat rekalkulasi: ' . $e->getMessage());
        }
    }


    /**
     * Get the monthly summary of kas transactions.
     */
    private function getMonthlySummary($month, $year)
    {
        // Get the first day of the month
        $firstDayOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        // Get the last transaction before the start of the month to get opening balance
        $previousTransaction = KasTransaction::where('transaction_date', '<', $firstDayOfMonth)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $openingBalance = $previousTransaction ? $previousTransaction->balance : 0;

        // Get sum of credits and debits for the month
        $summary = KasTransaction::forMonthYear($month, $year)
            ->select(
                DB::raw('SUM(credit) as total_credit'),
                DB::raw('SUM(debit) as total_debit')
            )
            ->first();

        $totalCredit = $summary->total_credit ?? 0;
        $totalDebit = $summary->total_debit ?? 0;

        // Calculate closing balance
        $closingBalance = $openingBalance + $totalCredit - $totalDebit;

        return [
            'opening_balance' => $openingBalance,
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'closing_balance' => $closingBalance
        ];
    }

    /**
     * Get total summary of all kas transactions.
     */
    private function getTotalSummary()
    {
        $summary = KasTransaction::select(
            DB::raw('SUM(credit) as total_credit'),
            DB::raw('SUM(debit) as total_debit')
        )
            ->first();

        $totalCredit = $summary->total_credit ?? 0;
        $totalDebit = $summary->total_debit ?? 0;
        $balance = $totalCredit - $totalDebit;

        return [
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'balance' => $balance
        ];
    }

    /**
     * Get the balance from transactions before the specified month and year.
     */
    private function getBalanceBefore($month, $year)
    {
        $firstDayOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        $previousTransaction = KasTransaction::where('transaction_date', '<', $firstDayOfMonth)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $previousTransaction ? $previousTransaction->balance : 0;
    }

    /**
     * Update balances for all future transactions after a transaction is added or modified.
     */
    private function updateFutureBalances(KasTransaction $transaction)
    {
        $laterTransactions = KasTransaction::where(function ($query) use ($transaction) {
            $query->where('transaction_date', '>', $transaction->transaction_date)
                ->orWhere(function ($q) use ($transaction) {
                    $q->where('transaction_date', $transaction->transaction_date)
                        ->where('id', '>', $transaction->id);
                });
        })
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        if ($laterTransactions->count() > 0) {
            $runningBalance = $transaction->balance;

            foreach ($laterTransactions as $laterTransaction) {
                $runningBalance = $runningBalance + $laterTransaction->credit - $laterTransaction->debit;
                $laterTransaction->balance = $runningBalance;
                $laterTransaction->save();
            }
        }
    }
}
