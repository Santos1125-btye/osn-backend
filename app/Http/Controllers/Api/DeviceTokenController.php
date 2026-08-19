<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeviceTokenController extends Controller
{
    /**
     * Register or refresh an FCM device token.
     */
    public function store(Request $request)
    {
        $validator = validator($request->all(), [
            'token' => [
                'required',
                'string',
                'min:20',
                'max:4096',
            ],

            'app' => [
                'required',
                'in:customer,provider',
            ],

            'platform' => [
                'nullable',
                'string',
                'in:android',
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'app_version' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        /*
         * Customer app must register against customer accounts.
         * Provider app must register against provider accounts.
         */
        if ($user->role !== $request->app) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid app type for this account.',
            ], 403);
        }

        $token = trim($request->token);

        $tokenHash = hash('sha256', $token);

        /*
         * A Firebase token belongs to one app installation.
         *
         * If the same token is somehow registered under another
         * account, move it to the current authenticated account.
         */
        $deviceToken = DeviceToken::where(
            'token_hash',
            $tokenHash
        )->first();

        if ($deviceToken) {
            $deviceToken->update([
                'user_id' => $user->id,
                'token' => $token,
                'app' => $request->app,
                'platform' => $request->platform ?? 'android',
                'device_name' => $request->device_name,
                'app_version' => $request->app_version,
                'last_seen_at' => now(),
                'revoked_at' => null,
            ]);
        } else {
            $deviceToken = DeviceToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'token_hash' => $tokenHash,
                'app' => $request->app,
                'platform' => $request->platform ?? 'android',
                'device_name' => $request->device_name,
                'app_version' => $request->app_version,
                'last_seen_at' => now(),
                'revoked_at' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully.',
            'device_token' => [
                'id' => $deviceToken->id,
                'app' => $deviceToken->app,
                'platform' => $deviceToken->platform,
                'last_seen_at' => $deviceToken->last_seen_at,
            ],
        ]);
    }

    /**
     * Revoke a specific device token.
     */
    public function destroy(Request $request)
    {
        $validator = validator($request->all(), [
            'token' => [
                'required',
                'string',
                'min:20',
                'max:4096',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tokenHash = hash(
            'sha256',
            trim($request->token)
        );

        $deviceToken = DeviceToken::where(
            'token_hash',
            $tokenHash
        )
            ->where('user_id', $request->user()->id)
            ->first();

        if ($deviceToken) {
            $deviceToken->update([
                'revoked_at' => now(),
            ]);
        }

        /*
         * Deliberately return success even when the token
         * doesn't exist. This makes logout idempotent.
         */
        return response()->json([
            'success' => true,
            'message' => 'Device token revoked successfully.',
        ]);
    }
}