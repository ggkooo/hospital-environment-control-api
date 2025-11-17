<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;

class SectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Sector::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge(['active' => $request->get('active', false)]);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'chief' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'active' => 'boolean',
        ]);

        $sector = Sector::create($request->all());

        return response()->json($sector, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sector = Sector::findOrFail($id);
        return response()->json($sector);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sector = Sector::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'chief' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'active' => 'boolean',
        ]);

        $sector->update($request->all());

        return response()->json($sector);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sector = Sector::findOrFail($id);
        $sector->delete();

        return response()->json(['message' => 'Sector deleted successfully']);
    }
}
