<?php

namespace App\Services;

use App\Models\LoginCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Six-digit sign-in codes sent by email. Codes are stored hashed, live 10 minutes, allow 5 tries,
 * and a new code cancels any older unused one for the same email and purpose.
 */
class LoginCodeService
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function issue(string $email, string $purpose): string
    {
        $email = mb_strtolower(trim($email));
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        LoginCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        LoginCode::query()->create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        Mail::raw(
            "Your Dikho code is {$code}. It works for ".self::TTL_MINUTES.' minutes. If you did not ask for it, ignore this email.',
            fn ($message) => $message->to($email)->subject("{$code} is your Dikho code"),
        );

        return $code;
    }

    /**
     * Checks a code. Returns null on success, else a message for the person.
     */
    public function verify(string $email, string $code, string $purpose): ?string
    {
        $record = LoginCode::query()
            ->where('email', mb_strtolower(trim($email)))
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($record === null || $record->expires_at->isPast()) {
            return 'This code has expired. Ask for a new one.';
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            return 'Too many wrong tries. Ask for a new code.';
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');
            $left = self::MAX_ATTEMPTS - $record->attempts;

            return $left > 0 ? "That code is not right. {$left} ".($left === 1 ? 'try' : 'tries').' left.' : 'Too many wrong tries. Ask for a new code.';
        }

        $record->update(['consumed_at' => now()]);

        return null;
    }
}
