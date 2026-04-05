<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:shipping,billing'],
        ]);

        $address = Address::create(array_merge(
            $request->validated(),
            ['user_id' => auth()->id()]
        ));

        return response()->json(['success' => true, 'address' => $address]);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
        ]);

        $address->update($request->validated());

        return response()->json(['success' => true, 'address' => $address->fresh()]);
    }

    public function destroy(Address $address): JsonResponse
    {
        if ($address->user_id !== auth()->id()) {
            abort(403);
        }

        $address->delete();

        return response()->json(['success' => true, 'message' => 'Address deleted.']);
    }
}
