<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProviderBankAccountRequest;
use App\Models\ProviderBankAccount;
use App\Services\BankAccountService;
use Illuminate\Support\Facades\Auth;

class ProviderBankAccountController extends Controller
{
    /**
     * List bank accounts
     */
    public function index()
    {
        return response()->json(

            Auth::user()
                ->provider
                ->bankAccounts
        );
    }

    /**
     * Add bank account
     */
    public function store(
        ProviderBankAccountRequest $request
    ) {

        $provider = Auth::user()->provider;

        if ($request->boolean('is_default')) {

            $provider->bankAccounts()
                ->update([
                    'is_default' => false
                ]);

        }

        $resolved = BankAccountService::resolve(
            $request->account_number,
            $request->bank_code
        );

        if (
            !isset($resolved['status']) ||
            !$resolved['status']
        ) {

            return response()->json([
                'message' => 'Invalid bank account.'
            ], 422);

        }

        $account = ProviderBankAccount::create([

            'provider_id' => $provider->id,

            'bank_code' => $request->bank_code,

            'bank_name' => $request->bank_name,

            'account_name' => $resolved['data']['account_name'],

            'account_number' => $resolved['data']['account_number'],

            'is_default' =>
                $request->boolean('is_default'),

            'is_verified' => true,

        ]);

        return response()->json([
            'message' => 'Bank account added.',
            'data' => $account,
        ], 201);
    }

    public function banks()
    {
        return response()->json(
            BankAccountService::banks()
        );
    }

    public function resolve(Request $request)
    {
        $request->validate([
            'bank_code' => ['required', 'string'],
            'account_number' => ['required', 'digits:10'],
        ]);

        $response = BankAccountService::resolve(
            $request->account_number,
            $request->bank_code
        );

        if (!$response['status']) {
            return response()->json([
                'success' => false,
                'message' => $response['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'account_name' => $response['data']['account_name'],
        ]);
    }

    public function destroy(ProviderBankAccount $bankAccount)
    {
        $provider = Auth::user()->provider;

        abort_unless(
            $bankAccount->provider_id === $provider->id,
            403,
            'Unauthorized.'
        );

        $wasDefault = $bankAccount->is_default;

        $bankAccount->delete();

        // If the deleted account was the default,
        // automatically make another account the default.
        if ($wasDefault) {
            $nextAccount = $provider->bankAccounts()
                ->latest()
                ->first();

            if ($nextAccount) {
                $nextAccount->update([
                    'is_default' => true,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bank account deleted successfully.',
        ]);
    }

    public function setDefault(ProviderBankAccount $bankAccount)
    {
        $provider = Auth::user()->provider;

        abort_unless(
            $bankAccount->provider_id === $provider->id,
            403,
            'Unauthorized.'
        );

        $provider->bankAccounts()->update([
            'is_default' => false,
        ]);

        $bankAccount->update([
            'is_default' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Default bank account updated successfully.',
            'data' => $bankAccount,
        ]);
    }
}