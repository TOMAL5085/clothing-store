<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, OtpService $otpService): JsonResponse
    {
        $user = User::create([...$request->validated(), 'role' => 'customer', 'status' => 'active']);
        $issued = $otpService->issue($user);

        return response()->json([
            'data' => [
                'user' => UserResource::make($user),
                'token' => $user->createToken('frontend')->plainTextToken,
                'verificationRequired' => true,
                'debugOtp' => app()->environment('testing') || config('app.debug') ? $issued['code'] : null,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages(['email' => 'This account is not active. Please contact customer care.']);
        }

        return response()->json([
            'data' => [
                'user' => UserResource::make($user),
                'token' => $user->createToken('frontend')->plainTextToken,
            ],
        ]);
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
