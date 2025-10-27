<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AirlineCommission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AirlineCommissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $commissions = AirlineCommission::orderBy('airline_code')->get();

        return view('admin.airline-commissions.index', [
            'commissions' => $commissions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->filled('airline_code')) {
            $request->merge(['airline_code' => strtoupper(trim($request->input('airline_code')))]);
        }

        $validated = $request->validate([
            'airline_code' => ['required', 'string', 'min:2', 'max:3', 'regex:/^[A-Z0-9]+$/', 'unique:airline_commissions,airline_code'],
            'airline_name' => ['nullable', 'string', 'max:255'],
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'flat_markup' => ['required', 'numeric', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        AirlineCommission::create($validated);

        return redirect()
            ->route('admin.airline-commissions.index')
            ->with('status', 'Commission created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AirlineCommission $airlineCommission)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AirlineCommission $airlineCommission)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AirlineCommission $airlineCommission)
    {
        $validated = $request->validate([
            'airline_name' => ['nullable', 'string', 'max:255'],
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'flat_markup' => ['required', 'numeric', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $airlineCommission->update($validated);

        return redirect()
            ->route('admin.airline-commissions.index')
            ->with('status', 'Commission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AirlineCommission $airlineCommission)
    {
        $airlineCommission->delete();

        return redirect()
            ->route('admin.airline-commissions.index')
            ->with('status', 'Commission deleted.');
    }
}
