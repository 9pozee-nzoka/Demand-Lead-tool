<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     *
     * Creates a new organization and its owner user in a single transaction.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $organization = \DB::transaction(function () use ($data) {
            $org = Organization::create([
                'name'     => $data['organization_name'],
                'slug'     => Str::slug($data['organization_name']).'-'.Str::random(4),
                'industry' => $data['industry'] ?? null,
                'country'  => $data['country'] ?? null,
                'timezone' => $data['timezone'] ?? 'UTC',
                'status'   => 'active',
            ]);

            $user = User::create([
                'organization_id' => $org->id,
                'name'            => $data['name'],
                'email'           => $data['email'],
                'phone'           => $data['phone'] ?? null,
                'password'        => $data['password'], // cast handles hashing
                'role'            => 'owner',
                'status'          => 'active',
            ]);

            AuditLog::record('organization.created', $org, [], $org->id, $user->id);

            return ['organization' => $org, 'user' => $user];
        });

        $token = $organization['user']->createToken(
            $request->input('device', 'api'),
            ['*']
        )->plainTextToken;

        return response()->json([
            'message'      => 'Registration successful.',
            'token'        => $token,
            'token_type'   => 'Bearer',
            'user'         => $organization['user']->load('organization'),
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Your account is not active.'], 403);
        }

        // Revoke previous tokens for same device name to prevent token sprawl
        $device = $request->input('device', 'api');
        $user->tokens()->where('name', $device)->delete();

        $token = $user->createToken($device, ['*'])->plainTextToken;

        AuditLog::record('user.login', $user, ['ip' => $request->ip()]);

        return response()->json([
            'message'    => 'Login successful.',
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $user->load('organization'),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token used for this request
        $request->user()->currentAccessToken()->delete();

        AuditLog::record('user.logout', $request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load('organization.plan')
        );
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            // Return generic message to prevent email enumeration
            return response()->json([
                'message' => 'If that email exists, a reset link has been sent.',
            ]);
        }

        return response()->json([
            'message' => 'Password reset link sent.',
        ]);
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                AuditLog::record('user.password_reset', $user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Password has been reset.']);
    }
}
