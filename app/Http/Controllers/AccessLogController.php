<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return AccessLog::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'timestamp' => 'required|date',
            'user' => 'required|string|max:255',
            'role' => 'required|string|max:100',
            'action' => 'required|string|max:100',
            'page' => 'required|string|max:255',
            'ip' => 'required|string|max:45',
            'user_agent' => 'required|string',
            'city' => 'required|string|max:100',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $log = AccessLog::create($validated);

        return response()->json($log, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
