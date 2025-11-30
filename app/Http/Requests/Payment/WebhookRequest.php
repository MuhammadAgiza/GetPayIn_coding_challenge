<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentStatusEnum;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;
class WebhookRequest extends ApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string'],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'status' => ['required' , 'string' , Rule::enum(PaymentStatusEnum::class)],
        ];
    }
}
