<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\ApiRequest;
class StoreOrderRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hold_id' => ['required', 'exists:holds,id'],
        ];
    }
}
