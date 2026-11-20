<?php

namespace App\Http\Controllers;

use App\Support\Panduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanduanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $q = trim((string) $request->input('q', ''));

        $artikel = $q !== '' ? Panduan::cari($user, $q) : Panduan::untuk($user);
        $perKategori = $q !== '' ? collect() : $artikel->groupBy('kategori');

        return view('panduan.index', [
            'q' => $q,
            'artikel' => $artikel,
            'perKategori' => $perKategori,
            'total' => Panduan::untuk($user)->count(),
            'layout' => $this->layout($user),
        ]);
    }

    public function show(string $slug)
    {
        $user = Auth::user();
        $artikel = Panduan::cariSlug($user, $slug);
        abort_unless($artikel, 404);

        $semua = Panduan::untuk($user);
        $terkait = $semua
            ->where('kategori', $artikel['kategori'])
            ->where('slug', '!=', $slug)
            ->take(6)
            ->values();

        return view('panduan.show', [
            'artikel' => $artikel,
            'terkait' => $terkait,
            'layout' => $this->layout($user),
        ]);
    }

    /** Operator (driver) memakai layout mobile tanpa sidebar. */
    private function layout($user): string
    {
        return $user->isOperator() ? 'layouts.operator' : 'layouts.app';
    }
}
