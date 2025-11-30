<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(protected ProductService $productService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $paginated = $this->productService->getPaginatedProducts($perPage, $page);

        return ApiResponse::success($paginated->toResourceCollection());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            return ApiResponse::notFound('product not found');
        }

        return ApiResponse::success(['product' => $product->toResource()]);
    }
}
