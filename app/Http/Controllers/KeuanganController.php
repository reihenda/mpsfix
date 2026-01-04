<?php

namespace App\Http\Controllers;

use App\Models\KasTransaction;
use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KeuanganController extends Controller
{
    /**
     * Display a listing of the financial data.
     */
    public function index()
    {
        // Get Kas summary
        $kasSummary = $this->getKasSummary();
        
        // Get Bank summary
        $bankSummary = $this->getBankSummary();
        
        // Calculate total financials
        $totalFinancials = [
            'total_credit' => $kasSummary['total_credit'] + $bankSummary['total_credit'],
            'total_debit' => $kasSummary['total_debit'] + $bankSummary['total_debit'],
            'balance' => $kasSummary['balance'] + $bankSummary['balance']
        ];
        
        return view('keuangan.index', compact('kasSummary', 'bankSummary', 'totalFinancials'));
    }
    
    /**
     * Get summary of all KAS transactions.
     */
    private function getKasSummary()
    {
        $summary = KasTransaction::select(
                DB::raw('SUM(credit) as total_credit'),
                DB::raw('SUM(debit) as total_debit')
            )
            ->first();
        
        $totalCredit = $summary->total_credit ?? 0;
        $totalDebit = $summary->total_debit ?? 0;
        $balance = $totalCredit - $totalDebit;
        
        // Get latest transaction
        $latestTransaction = KasTransaction::orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        
        return [
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'balance' => $balance,
            'latest_transaction' => $latestTransaction,
        ];
    }
    
    /**
     * Get summary of all BANK transactions.
     */
    private function getBankSummary()
    {
        $summary = BankTransaction::select(
                DB::raw('SUM(credit) as total_credit'),
                DB::raw('SUM(debit) as total_debit')
            )
            ->first();
        
        $totalCredit = $summary->total_credit ?? 0;
        $totalDebit = $summary->total_debit ?? 0;
        $balance = $totalCredit - $totalDebit;
        
        // Get latest transaction
        $latestTransaction = BankTransaction::orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        
        return [
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'balance' => $balance,
            'latest_transaction' => $latestTransaction,
        ];
    }
}
