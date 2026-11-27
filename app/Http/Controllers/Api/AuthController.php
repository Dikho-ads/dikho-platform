<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountSummary;
use App\Services\LoginCodeService;
use App\Services\ShopSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private LoginCodeService $codes, private AccountSummary $summary) {}

    public function requestCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'purpose' => ['required', Rule::in(['login', 'signup'])],
            'name' => ['exclude_unless:purpose,signup', 'required', 'string', 'min:2', 'max:80'],
            'phone' => ['exclude_unless:purpose,signup', 'required', 'digits:10', 'regex:/^[6-9]/'],
        ], [
            'phone.digits' => 'Enter the 10-digit mobile number.',
            'phone.regex' => 'Indian mobile numbers start with 6, 7, 8 or 9.',
        ]);

        $email = mb_strtolower(trim($data['email']));
        $exists = User::query()->where('email', $email)->exists();

        if ($data['purpose'] === 'login' && ! $exists) {
            throw ValidationException::withMessages(['email' => 'No account uses this email yet. Create one instead.']);
        }

        if ($data['purpose'] === 'signup' && $exists) {
            throw ValidationException::withMessages(['email' => 'An account already uses this email. Sign in instead.']);
        }

        $code = $this->codes->issue($email, $data['purpose']);

        return response()->json(array_filter([
            'sent' => true,
            'email' => $email,
            'expires_in' => LoginCodeService::TTL_MINUTES * 60,
            // Local and testing only: there is no mail server, so the code is returned to the page.
            'debug_code' => app()->environment('local', 'testing') ? $code : null,
        ], fn ($value): bool => $value !== null));
    }

    public function verify(Request $request, ShopSetup $setup): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'code' => ['required', 'digits:6'],
            'purpose' => ['required', Rule::in(['login', 'signup'])],
            'name' => ['exclude_unless:purpose,signup', 'required', 'string', 'min:2', 'max:80'],
            'phone' => ['exclude_unless:purpose,signup', 'required', 'digits:10', 'regex:/^[6-9]/'],
        ], ['code.digits' => 'The code has 6 digits.']);

        $email = mb_strtolower(trim($data['email']));

        if ($error = $this->codes->verify($email, $data['code'], $data['purpose'])) {
            throw ValidationException::withMessages(['code' => $error]);
        }

        $user = $data['purpose'] === 'signup'
            ? DB::transaction(function () use ($data, $email, $setup): User {
                $user = User::query()->firstOrCreate(['email' => $email], [
                    'name' => trim($data['name']),
                    'phone' => $data['phone'],
                    'locale' => 'en',
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();

                if (! $user->ownedShop()->exists()) {
                    $setup->createFor($user, trim($data['name']).'’s shop', '+91 '.substr($data['phone'], 0, 5).' '.substr($data['phone'], 5));
                }

                return $user;
            })
            : User::query()->where('email', $email)->firstOrFail();

        $token = $user->createToken('web', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json(['token' => $token, 'account' => $this->summary->for($user->refresh())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->summary->for($request->user()));
    }
}
