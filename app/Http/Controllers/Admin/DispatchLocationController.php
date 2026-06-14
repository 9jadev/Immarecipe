<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DispatchLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DispatchLocationController extends Controller
{
    public function index()
    {
        $dispatchLocations = DispatchLocation::ordered()->get();

        return Inertia::render('admin/dispatch-locations/Index', [
            'dispatchLocations' => $dispatchLocations,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/dispatch-locations/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:dispatch_locations,name'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        DispatchLocation::create($validated);

        return redirect()->route('admin.dispatch-locations.index')
            ->with('success', 'Dispatch location created successfully.');
    }

    public function edit(DispatchLocation $dispatchLocation)
    {
        return Inertia::render('admin/dispatch-locations/Form', [
            'dispatchLocation' => $dispatchLocation,
        ]);
    }

    public function update(Request $request, DispatchLocation $dispatchLocation)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:dispatch_locations,name,' . $dispatchLocation->id],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $dispatchLocation->update($validated);

        return redirect()->route('admin.dispatch-locations.index')
            ->with('success', 'Dispatch location updated successfully.');
    }

    public function destroy(DispatchLocation $dispatchLocation)
    {
        $dispatchLocation->delete();

        return redirect()->route('admin.dispatch-locations.index')
            ->with('success', 'Dispatch location deleted successfully.');
    }

    public function toggleActive(DispatchLocation $dispatchLocation)
    {
        $dispatchLocation->update([
            'is_active' => !$dispatchLocation->is_active,
        ]);

        return back()->with('success', 'Dispatch location status updated successfully.');
    }
}
