<?php

namespace App\Http\Controllers;

use App\DTOs\OrderDTO;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Responses\ApiResponse;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $req = $request->validated();
        $dto = OrderDTO::fromArray($req);
        $order = $this->orderService->createOrder($dto);
        return ApiResponse::created(['order' => $order->toResource()]);
    }
}
