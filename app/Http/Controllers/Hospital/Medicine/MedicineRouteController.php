<?php

namespace App\Http\Controllers\Hospital\Medicine;

use App\Http\Controllers\Controller;
use App\Models\Hospital\MedicineRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineRouteController extends Controller
{
    public function index(string $slug): View
    {
        $routes = MedicineRoute::latest()->get();

        return view('hospital.medicine_routes.index', compact('routes', 'slug'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        MedicineRoute::create(['name' => $request->name]);

        return redirect()
            ->route('hospital.medicine-routes.index', ['slug' => $slug])
            ->with('success', 'Route of administration added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $route = MedicineRoute::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $route->update(['name' => $request->name]);

        return redirect()
            ->route('hospital.medicine-routes.index', ['slug' => $slug])
            ->with('success', 'Route of administration updated.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        MedicineRoute::findOrFail($id)->delete();

        return redirect()
            ->route('hospital.medicine-routes.index', ['slug' => $slug])
            ->with('success', 'Route of administration deleted.');
    }
}
