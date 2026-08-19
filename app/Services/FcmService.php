<?php

namespace App\Services;

use App\Models\DeviceToken;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FcmService
{
    private const FCM_SCOPE =
        'https://www.googleapis.com/auth/firebase.messaging';

    private const FCM_ENDPOINT =
        'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    /**
     * Send a notification to every active device belonging to a user.
     *
     * Returns a summary of successful, failed and revoked tokens.
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = []
    ): array {
        $devices = DeviceToken::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->get();

        if ($devices->isEmpty()) {
            return [
                'success' => true,
                'sent' => 0,
                'failed' => 0,
                'revoked' => 0,
            ];
        }

        $results = [
            'success' => true,
            'sent' => 0,
            'failed' => 0,
            'revoked' => 0,
        ];

        foreach ($devices as $device) {
            try {
                $result = $this->sendToToken(
                    token: $device->token,
                    title: $title,
                    body: $body,
                    data: $data,
                );

                if ($result['sent']) {
                    $results['sent']++;
                }

                if ($result['failed']) {
                    $results['failed']++;
                }

                if ($result['revoked']) {
                    $results['revoked']++;
                }
            } catch (\Throwable $e) {
                $results['failed']++;

                Log::error(
                    'FCM device notification failed.',
                    [
                        'device_token_id' => $device->id,
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        return $results;
    }

    /**
     * Send a notification to one specific device token.
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): array {
        $token = trim($token);

        if ($token === '') {
            throw new RuntimeException(
                'FCM device token cannot be empty.'
            );
        }

        $accessToken = $this->getAccessToken();

        $projectId = $this->getProjectId();

        $endpoint = sprintf(
            self::FCM_ENDPOINT,
            $projectId
        );

        $payload = [
            'message' => [
                'token' => $token,

                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],

                'data' => $this->normalizeData($data),

                'android' => [
                    'priority' => 'HIGH',

                    'notification' => [
                        'channel_id' => 'osn_notifications',
                        'sound' => 'default',
                        'notification_priority' => 'PRIORITY_HIGH',
                    ],
                ],
            ],
        ];

        /** @var Response $response */
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post($endpoint, $payload);

        if ($response->successful()) {
            return [
                'sent' => true,
                'failed' => false,
                'revoked' => false,
            ];
        }

        if ($this->isInvalidTokenResponse($response)) {
            $this->revokeInvalidToken($token);

            return [
                'sent' => false,
                'failed' => true,
                'revoked' => true,
            ];
        }

        Log::error(
            'FCM HTTP v1 request failed.',
            [
                'status' => $response->status(),
                'response' => $this->safeResponseBody(
                    $response
                ),
            ]
        );

        return [
            'sent' => false,
            'failed' => true,
            'revoked' => false,
        ];
    }

    /**
     * Obtain a short-lived OAuth 2 access token from
     * the Firebase service account.
     */
    private function getAccessToken(): string
    {
        $credentialsPath =
            config('firebase.credentials');

        if (
            !is_string($credentialsPath) ||
            !is_file($credentialsPath)
        ) {
            throw new RuntimeException(
                'Firebase service-account file was not found: '
                . $credentialsPath
            );
        }

        $credentials = new ServiceAccountCredentials(
            self::FCM_SCOPE,
            $credentialsPath
        );

        $token = $credentials->fetchAuthToken();

        if (
            !is_array($token) ||
            empty($token['access_token'])
        ) {
            throw new RuntimeException(
                'Unable to obtain Firebase OAuth access token.'
            );
        }

        return $token['access_token'];
    }

    /**
     * Read the Firebase project ID directly from the
     * service-account JSON.
     */
    private function getProjectId(): string
    {
        $credentialsPath =
            config('firebase.credentials');

        if (
            !is_string($credentialsPath) ||
            !is_file($credentialsPath)
        ) {
            throw new RuntimeException(
                'Firebase service-account file was not found.'
            );
        }

        $contents = file_get_contents(
            $credentialsPath
        );

        if ($contents === false) {
            throw new RuntimeException(
                'Unable to read Firebase service-account file.'
            );
        }

        $credentials = json_decode(
            $contents,
            true
        );

        if (
            !is_array($credentials) ||
            empty($credentials['project_id'])
        ) {
            throw new RuntimeException(
                'Firebase service-account JSON does not contain a project_id.'
            );
        }

        return $credentials['project_id'];
    }

    /**
     * FCM data payload values must be strings.
     */
    private function normalizeData(
        array $data
    ): array {
        $normalized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $normalized[(string) $key] =
                    $value ? 'true' : 'false';

                continue;
            }

            if (is_scalar($value)) {
                $normalized[(string) $key] =
                    (string) $value;

                continue;
            }

            $normalized[(string) $key] =
                json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                );
        }

        return $normalized;
    }

    /**
     * FCM returns UNREGISTERED when the token is no longer valid.
     *
     * INVALID_ARGUMENT is only revoked when the error details
     * explicitly indicate an invalid registration token.
     */
    private function isInvalidTokenResponse(
        Response $response
    ): bool {
        if ($response->status() === 404) {
            return true;
        }

        $json = $response->json();

        if (!is_array($json)) {
            return false;
        }

        $status =
            data_get(
                $json,
                'error.status'
            );

        if ($status === 'UNREGISTERED') {
            return true;
        }

        if (
            $status !== 'INVALID_ARGUMENT'
        ) {
            return false;
        }

        $details =
            data_get(
                $json,
                'error.details',
                []
            );

        foreach ($details as $detail) {
            $errorCode =
                data_get(
                    $detail,
                    'errorCode'
                );

            if (
                $errorCode ===
                'UNREGISTERED'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Revoke an invalid Firebase token instead of deleting
     * it so the registration history remains auditable.
     */
    private function revokeInvalidToken(
        string $token
    ): void {
        $hash = hash(
            'sha256',
            trim($token)
        );

        DeviceToken::query()
            ->where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
            ]);
    }

    /**
     * Never log FCM tokens or OAuth credentials.
     */
    private function safeResponseBody(
        Response $response
    ): array|string|null {
        $json = $response->json();

        if (is_array($json)) {
            return [
                'error' => data_get(
                    $json,
                    'error.status'
                ),
                'message' => data_get(
                    $json,
                    'error.message'
                ),
            ];
        }

        return $response->body();
    }
}