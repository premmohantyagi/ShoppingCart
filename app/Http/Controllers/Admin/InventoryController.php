<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\LowStockAlert;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(Request $request): View
    {
        $stockItems = StockItem::with(['product', 'productVariant', 'warehouse'])
            ->when($request->input('warehouse_id'), fn ($q, $w) => $q->where('warehouse_id', $w))
            ->when($request->input('product_id'), fn ($q, $p) => $q->where('product_id', $p))
            ->when($request->input('search'), fn ($q, $s) => $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$s}%")))
            ->latest()
            ->paginate(15);

        return view('admin.inventory.index', compact('stockItems'));
    }

    public function setStock(Request $request): RedirectResponse
    {
        $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->inventoryService->setStock(
            (int) $request->input('warehouse_id'),
            (int) $request->input('product_id'),
            $request->input('product_variant_id') ? (int) $request->input('product_variant_id') : null,
            (int) $request->input('quantity'),
            auth()->id(),
        );

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Stock set successfully.');
    }

    public function adjust(Request $request, StockItem $stockItem): RedirectResponse
    {
        $request->validate([
            'movement_type' => ['required', 'string', 'in:manual_in,manual_out,adjustment,damage_out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $quantity = (int) $request->input('quantity');
        $movementType = StockMovementType::from($request->input('movement_type'));

        if (in_array($request->input('movement_type'), ['manual_out', 'damage_out'])) {
            $quantity = -$quantity;
        }

        $this->inventoryService->adjustStock(
            $stockItem,
            $quantity,
            $movementType,
            $request->input('note'),
            auth()->id(),
        );

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Stock adjusted successfully.');
    }

    public function movements(): View
    {
        $movements = StockMovement::with(['product', 'productVariant', 'warehouse'])
            ->latest()
            ->paginate(15);

        return view('admin.inventory.movements', compact('movements'));
    }

    public function lowStockAlerts(): View
    {
        $alerts = LowStockAlert::with(['product', 'productVariant', 'warehouse'])
            ->where('is_sent', false)
            ->latest()
            ->paginate(15);

        return view('admin.inventory.low-stock', compact('alerts'));
    }
}
