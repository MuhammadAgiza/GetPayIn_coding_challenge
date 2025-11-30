<?php

namespace App\Http\Controllers;

use App\DTOs\HoldDTO;
use App\Http\Requests\Hold\StoreHoldRequest;
use App\Http\Responses\ApiResponse;
use App\Services\HoldService;
class HoldController extends Controller
{

    public function __construct(protected HoldService $holdService)
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHoldRequest $request)
    {
        $req = $request->validated();
        $dto = HoldDTO::fromArray($req);
        $hold = $this->holdService->createHold($dto);
        return ApiResponse::created(['hold' => $hold->toResource()]);
    }
}
