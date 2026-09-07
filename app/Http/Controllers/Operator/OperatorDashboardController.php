<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OperatorDashboardController extends Controller
{
    public function dashboard()
    {
        $operatorGtm = Auth::user()->operatorGtm;

        return view('operator.dashboard', compact('operatorGtm'));
    }
}
