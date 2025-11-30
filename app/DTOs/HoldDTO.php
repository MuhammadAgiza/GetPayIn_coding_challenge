<?php

namespace App\DTOs;
use Illuminate\Http\Request;

class HoldDTO
{

    public function __construct(
        public ?string $qty,
        public ?string $product_id,
    ) {
    }

    public static function fromRequest(Request $request)
    {
        return new self(
            qty: $request->input('qty'),
            product_id: $request->input('product_id'),
        );
    }

    public static function fromArray(array $data)
    {
        return new self(
            qty: $data['qty'] ?? null,
            product_id: $data['product_id'] ?? null,
        );
    }


    public function toArray()
    {
        $data = [
            'qty' => $this->qty ?? null,
            'product_id' => $this->product_id ?? null,
        ];

        return array_filter($data, fn($value) => $value !== null);
    }
}
