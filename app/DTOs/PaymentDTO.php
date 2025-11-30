<?php

namespace App\DTOs;
use Illuminate\Http\Request;
use App\Enums\PaymentStatusEnum;

class PaymentDTO
{

    public function __construct(
        public ?string $order_id,
        public ?string $reference,
        public ?PaymentStatusEnum $status,
        public ?string $payload,
    ) {
    }

    public static function fromRequest(Request $request)
    {
        return new self(
            order_id: $request->input('order_id'),
            reference: $request->input('reference'),
            status: $request->input('status') !== null ? PaymentStatusEnum::from($request->input('status')) : null,
            payload: $request->input('payload'),
        );
    }

    public static function fromArray(array $data)
    {
        return new self(
            order_id: $data['order_id'] ?? null,
            reference: $data['reference'] ?? null,
            status: isset($data['status']) ? PaymentStatusEnum::from($data['status']) : null,
            payload: $data['payload'] ?? null,
        );
    }


    public function toArray()
    {
        $data = [
            'order_id' => $this->order_id ?? null,
            'reference' => $this->reference ?? null,
            'status' => $this->status?->value ?? null,
            'payload' => $this->payload ?? null,
        ];

        return array_filter($data, fn($value) => $value !== null);
    }
}
