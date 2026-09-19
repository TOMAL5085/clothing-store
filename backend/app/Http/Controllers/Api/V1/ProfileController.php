<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, OtpService $otpService)
    {
        $user = $request->user();
        $data = $request->validated();
        $emailChanged = $data['email'] !== $user->email;

        $user->update($data);
        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        $debugOtp = null;
        if ($emailChanged) {
            $issued = $otpService->issue($user->refresh());
            $debugOtp = app()->environment('testing') || config('app.debug') ? $issued['code'] : null;
        }

        return response()->json([
            'data' => UserResource::make($user->refresh()),
            'debugOtp' => $debugOtp,
        ]);
    }

    public function password(UpdatePasswordRequest $request)
    {
        $request->user()->update(['password' => Hash::make($request->validated('password'))]);

        return response()->json(['message' => 'Password updated.']);
    }
}
