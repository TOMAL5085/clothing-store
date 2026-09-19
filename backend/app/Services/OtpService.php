<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const PURPOSE_EMAIL_VERIFICATION = 'email_verification';

    public function issue(User $user, string $purpose = self::PURPOSE_EMAIL_VERIFICATION): array
    {
        $latest = UserOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if ($latest?->last_sent_at?->gt(now()->subMinute())) {
            throw ValidationException::withMessages(['otp' => 'Please wait before requesting another verification code.']);
        }

        UserOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $otp = UserOtp::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'channel' => 'email',
            'code_hash' => Hash::make($code),
            'last_sent_at' => now(),
            'expires_at' => now()->addMinutes((int) config('auth.otp_expires_minutes', 10)),
        ]);

        return ['otp' => $otp, 'code' => $code];
    }

    public function verify(User $user, string $code, string $purpose = self::PURPOSE_EMAIL_VERIFICATION): void
    {
        $otp = UserOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages(['code' => 'No active verification code was found.']);
        }

        if ($otp->expires_at->isPast()) {
            $otp->update(['verified_at' => now()]);
            throw ValidationException::withMessages(['code' => 'Verification code has expired.']);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'Too many incorrect attempts. Request a new code.']);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages(['code' => 'Verification code is incorrect.']);
        }

        $otp->update(['verified_at' => now()]);

        if ($purpose === self::PURPOSE_EMAIL_VERIFICATION) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
