<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();
            if ($user->isSuperAdmin()) {
                return redirect()->route('superadmin.dashboard');
            } elseif ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->isKeuangan()) {
                return redirect()->route('keuangan.index');
            } elseif ($user->isDemo()) {
                return redirect()->route('demo.admin'); // Redirect to demo admin dashboard
            } elseif ($user->isFob()) {
                return redirect()->route('fob.dashboard');
            } else {
                return redirect()->route('customer.dashboard');
            }
        }

        return back()->withErrors([
            'email' => 'Email atau password salah',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function tambahUser(Request $request)
    {
        \Log::info('User data received:', $request->all());
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:3',
            'role' => 'required|string|in:admin,keuangan,customer,fob,demo',
            'no_kontrak' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'nomor_tlpn' => 'nullable|string|max:20',
        ], [
            'name.required' => 'Nama harus diisi',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'name.unique' => 'nama sudah digunakan',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 3 karakter',
            'role.required' => 'Role harus dipilih',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Buat user baru
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->role = $request->role;
            $user->no_kontrak = $request->no_kontrak;
            $user->alamat = $request->alamat;
            $user->nomor_tlpn = $request->nomor_tlpn;

            // Tambahkan nilai default untuk field lain jika diperlukan
            if ($request->role == 'customer') {
                $user->harga_per_meter_kubik = 0;
                $user->koreksi_meter = 1;
                $user->total_purchases = 0;
                $user->deposit_history = json_encode([]);
                $user->pricing_history = json_encode([]);
            }

            $user->save();

            return redirect()->back()->with('success', 'User berhasil ditambahkan');
        } catch (\Exception $e) {
            \Log::error('User creation failed: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return redirect()->back()
                ->with('error', 'Gagal menambahkan user: ' . $e->getMessage())
                ->withInput();
        }
    }
}
