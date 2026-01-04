<?php

namespace App\Http\Controllers;

use App\Models\Ukuran;
use Illuminate\Http\Request;

class UkuranController extends Controller
{
    /**
     * Get all ukuran.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll()
    {
        $ukuranList = Ukuran::orderBy('nama_ukuran')->get();
        return response()->json($ukuranList);
    }

    /**
     * Store a newly created ukuran.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_ukuran' => 'required|string|max:50|unique:ukuran,nama_ukuran',
        ]);

        $ukuran = Ukuran::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ukuran berhasil ditambahkan.',
            'ukuran' => $ukuran
        ]);
    }
}