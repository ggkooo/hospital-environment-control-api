<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Sector;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Role::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Role store request', $request->all());

        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'chief' => 'required|string',
            'sector' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        try {
            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'chief' => $request->chief,
                'sector' => $request->sector,
                'status' => $request->status ?? 'inactive',
                'permissions' => $request->permissions,
            ]);

            return response()->json($role, 201);
        } catch (\Exception $e) {
            \Log::error('Error creating role', ['error' => $e->getMessage(), 'data' => $request->all()]);
            return response()->json(['error' => 'Failed to create role', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Role::findOrFail($id);
        return response()->json($role);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'sector' => 'required|string',
            'status' => 'nullable|in:active,inactive',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $sector = Sector::where('name', $request->sector)->first();
        if (!$sector) {
            return response()->json(['error' => 'Sector not found'], 404);
        }

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'chief' => $sector->chief,
            'sector' => $request->sector,
            'status' => $request->status ?? $role->status,
            'permissions' => $request->permissions,
        ]);

        return response()->json($role);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        return response()->json(['message' => 'Role deleted']);
    }
}
