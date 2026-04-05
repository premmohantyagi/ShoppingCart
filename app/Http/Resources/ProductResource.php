<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'product_type' => $this->product_type,
            'regular_price' => $this->regular_price,
            'sale_price' => $this->sale_price,
            'current_price' => $this->current_price,
            'is_on_sale' => $this->is_on_sale,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ]),
            'vendor' => $this->whenLoaded('vendor', fn () => [
                'id' => $this->vendor->id,
                'business_name' => $this->vendor->business_name,
            ]),
            'thumbnail' => $this->getFirstMediaUrl('thumbnail', 'thumb'),
            'images' => $this->getMedia('gallery')->map(fn ($m) => $m->getUrl('medium')),
            'created_at' => $this->created_at,
        ];
    }
}
