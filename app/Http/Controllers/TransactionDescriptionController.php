<?php

namespace App\Http\Controllers;

use App\Models\TransactionDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionDescriptionController extends Controller
{
    /**
     * Display a listing of the descriptions.
     */
    public function index(Request $request)
    {
        $query = TransactionDescription::query();
        
        // Filter by category if specified
        if ($request->has('category') && in_array($request->category, ['kas', 'bank', 'both'])) {
            $query->where('category', $request->category);
        }
        
        $descriptions = $query->orderBy('description')->paginate(10);
        
        return view('keuangan.descriptions.index', compact('descriptions'));
    }

    /**
     * Show the form for creating a new description.
     */
    public function create()
    {
        return view('keuangan.descriptions.create');
    }

    /**
     * Store a newly created description in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255|unique:transaction_descriptions',
            'category' => 'required|in:kas,bank,both',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        TransactionDescription::create([
            'description' => $request->description,
            'category' => $request->category,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('keuangan.descriptions.index')
            ->with('success', 'Deskripsi transaksi berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified description.
     */
    public function edit(TransactionDescription $description)
    {
        return view('keuangan.descriptions.edit', compact('description'));
    }

    /**
     * Update the specified description in storage.
     */
    public function update(Request $request, TransactionDescription $description)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|max:255|unique:transaction_descriptions,description,' . $description->id,
            'category' => 'required|in:kas,bank,both',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $description->update([
            'description' => $request->description,
            'category' => $request->category,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('keuangan.descriptions.index')
            ->with('success', 'Deskripsi transaksi berhasil diperbarui.');
    }

    /**
     * Remove the specified description from storage.
     */
    public function destroy(TransactionDescription $description)
    {
        $description->delete();
        
        return redirect()->route('keuangan.descriptions.index')
            ->with('success', 'Deskripsi transaksi berhasil dihapus.');
    }
    
    /**
     * Get descriptions by category for AJAX request
     */
    public function getByCategory(Request $request)
    {
        $category = $request->category;
        
        $descriptions = TransactionDescription::active()
            ->inCategory($category)
            ->orderBy('description')
            ->get();
            
        return response()->json($descriptions);
    }
}
