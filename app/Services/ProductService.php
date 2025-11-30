<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function getPaginatedProducts($perPage = 10, $page = 1)
    {
        $cacheKey = "products:perPage:$perPage:page:$page";

        if ($cached = Cache::tags(['products'])->get($cacheKey)) {
            return $cached;
        }

        $paginated = Product::paginate(perPage: $perPage, page: $page);

        Cache::tags(['products'])->put($cacheKey, $paginated, 300);

        return $paginated;
    }

    public function getProductById($id)
    {
        $cacheKey = "product:$id";
        return Cache::remember($cacheKey, 300, function () use ($id) {
            return Product::find($id);
        });
    }

    public function flushCache(?int $id)
    {
        if ($id) {
            Cache::forget("product:{$id}");
        }
        Cache::tags(['products'])->flush();
    }
}
