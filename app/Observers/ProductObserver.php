<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function __construct(protected ProductService $productService)
    {
    }

    public function updated(Product $product): void
    {
        $this->productService->flushCache($product->id);
    }

    public function created(Product $product)
    {
        $this->productService->flushCache($product->id);
    }

    public function deleted(Product $product)
    {
        $this->productService->flushCache($product->id);
    }

}
