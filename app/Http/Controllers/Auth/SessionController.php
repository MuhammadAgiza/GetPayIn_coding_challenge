<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Responses\ApiResponse;

class SessionController extends Controller
{
    public function login(LoginRequest $request)
    {

        $validated = $request->validated();

        $user = User::where('email', $validated["email"])->first();

        if (!$user || !Hash::check($validated["password"], $user->password)) {
            return ApiResponse::unauthorized('Invalid credentials');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $responseData = [
            'token' => $token,
            'user' => $user->toArray(),
        ];

        return ApiResponse::success($responseData, 'Logged in successfully');
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        $user->currentAccessToken()->delete();
        return ApiResponse::success(['user' => $user->toArray()], 'Logged out successfully');
    }




}
