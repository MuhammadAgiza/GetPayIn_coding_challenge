<?php

namespace App\DTOs;
use Illuminate\Http\Request;

class OrderDTO
{

    public function __construct(
        public ?string $hold_id,
    ) {
    }

    public static function fromRequest(Request $request)
    {
        return new self(
            hold_id: $request->input('hold_id'),
        );
    }

    public static function fromArray(array $data)
    {
        return new self(
            hold_id: $data['hold_id'] ?? null,
        );
    }


    public function toArray()
    {
        $data = [
            'hold_id' => $this->hold_id ?? null,
        ];

        return array_filter($data, fn($value) => $value !== null);
    }
}
