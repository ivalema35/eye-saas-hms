<?php

namespace App\Http\Controllers\Hospital\Medicine;

use App\Http\Controllers\Controller;
use App\Models\Hospital\MedicineCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineCategoryController extends Controller
{
    public function index(string $slug): View
    {
        $categories = MedicineCategory::latest()->get();

        return view('hospital.medicine_categories.index', compact('categories', 'slug'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ], [
            'name.required' => 'Category name is required.',
        ]);

        MedicineCategory::create(['name' => $request->name]);

        return redirect()
            ->route('hospital.medicine-categories.index', ['slug' => $slug])
            ->with('success', 'Medicine category added successfully.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $category = MedicineCategory::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $category->update(['name' => $request->name]);

        return redirect()
            ->route('hospital.medicine-categories.index', ['slug' => $slug])
            ->with('success', 'Medicine category updated.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        MedicineCategory::findOrFail($id)->delete();

        return redirect()
            ->route('hospital.medicine-categories.index', ['slug' => $slug])
            ->with('success', 'Medicine category deleted.');
    }
}
