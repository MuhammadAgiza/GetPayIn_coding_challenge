<?php

namespace App\Http\Controllers;

use App\DTOs\PaymentDTO;
use App\Http\Requests\Payment\WebhookRequest;
use App\Http\Responses\ApiResponse;
use App\Services\PaymentService;

class PaymentLogController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {
    }
    public function webhook(WebhookRequest $request)
    {
        $req = $request->validated();
        $dto = PaymentDTO::fromArray($req);
        $dto->payload = json_encode($request->all());
        $this->paymentService->webhook($dto);
        return ApiResponse::success([]);
    }

}
