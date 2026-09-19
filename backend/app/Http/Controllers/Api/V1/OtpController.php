<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function store(Request $request, OtpService $otpService): JsonResponse
    {
        $issued = $otpService->issue($request->user());

        return response()->json([
            'message' => 'Verification code sent.',
            'debugOtp' => app()->environment('testing') || config('app.debug') ? $issued['code'] : null,
        ]);
    }

    public function verify(VerifyOtpRequest $request, OtpService $otpService): JsonResponse
    {
        $otpService->verify($request->user(), $request->validated('code'));

        return response()->json([
            'message' => 'Email verified.',
            'data' => UserResource::make($request->user()->refresh()),
        ]);
    }
}
