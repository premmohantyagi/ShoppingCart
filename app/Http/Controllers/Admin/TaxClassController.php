<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use App\Models\TaxClass;
use App\Services\TaxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxClassController extends Controller
{
    public function __construct(private TaxService $taxService) {}

    public function index(): View
    {
        $taxClasses = $this->taxService->getAllClasses();

        return view('admin.tax-classes.index', compact('taxClasses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['boolean'],
        ]);

        $this->taxService->storeClass($request->only(['name', 'description', 'is_default']));

        return redirect()->route('admin.tax-classes.index')
            ->with('success', 'Tax class created successfully.');
    }

    public function update(Request $request, TaxClass $taxClass): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['boolean'],
        ]);

        $this->taxService->updateClass($taxClass, $request->only(['name', 'description', 'is_default']));

        return redirect()->route('admin.tax-classes.index')
            ->with('success', 'Tax class updated successfully.');
    }

    public function destroy(TaxClass $taxClass): RedirectResponse
    {
        $this->taxService->deleteClass($taxClass);

        return redirect()->route('admin.tax-classes.index')
            ->with('success', 'Tax class deleted successfully.');
    }

    public function storeTax(Request $request, TaxClass $taxClass): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'region' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $this->taxService->addTax($taxClass, $request->only(['name', 'rate', 'region', 'is_active']));

        return redirect()->route('admin.tax-classes.index')
            ->with('success', 'Tax rate added successfully.');
    }

    public function destroyTax(Tax $tax): RedirectResponse
    {
        $this->taxService->deleteTax($tax);

        return redirect()->route('admin.tax-classes.index')
            ->with('success', 'Tax rate deleted successfully.');
    }
}
