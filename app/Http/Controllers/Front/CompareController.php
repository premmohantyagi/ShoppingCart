<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\CompareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function __construct(private CompareService $compareService)
    {
    }

    public function index(): View
    {
        $items = $this->compareService->getItems(
            auth()->id(),
            session()->getId()
        );

        return view('front.compare', compact('items'));
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate(['product_id' => ['required', 'exists:products,id']]);

        try {
            $this->compareService->addItem(
                auth()->id(),
                session()->getId(),
                $request->integer('product_id'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Added to compare.',
                'compare_count' => $this->compareService->getCount(auth()->id(), session()->getId()),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $this->compareService->removeItem(
            auth()->id(),
            session()->getId(),
            $request->integer('product_id'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Removed from compare.',
            'compare_count' => $this->compareService->getCount(auth()->id(), session()->getId()),
        ]);
    }

    public function clear(): JsonResponse
    {
        $this->compareService->clearAll(auth()->id(), session()->getId());

        return response()->json(['success' => true, 'message' => 'Compare list cleared.', 'compare_count' => 0]);
    }
}
