<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responses\ApiResponse;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create($validated);

        $token = $user->createToken('auth_token')->plainTextToken;

        $responseData = [
            'token' => $token,
            'user' => $user->toArray(),
        ];

        return ApiResponse::created($responseData, 'User registered successfully');
    }
}
