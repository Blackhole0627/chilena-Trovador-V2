<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\Notifications;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use App\Services\AgeVerificationInterface;

final class DiditService implements AgeVerificationInterface
{
    private $baseUrl = 'https://verification.didit.me';
    protected $apiKey;
    protected $webhookSecret;
    protected $workflowId;


    public function __construct()
    {
        $this->apiKey = config('settings.age_verification_didit_api_key');
        $this->webhookSecret = config('settings.age_verification_didit_webhook_secret');
        $this->workflowId = config('settings.age_verification_didit_workflow_id');
    }

    public function verify(): RedirectResponse
    {
        try {
            $client = new Client();
            $response = $client->request('POST', $this->baseUrl . '/v2/session/', [
                'json' => [
                    'workflow_id' => $this->workflowId,
                    'callback' => route('age.webhook', ['id' => auth()->id()]),
                ],
                'headers' => [
                    'accept' => 'application/json',
                    'x-api-key' => $this->apiKey,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return redirect()->away($data['url']);
            } else {
                Log::error('Error creating Didit session: ' . $data);
                return redirect()
                    ->route('verify.age')
                    ->withErrorVerification($data['detail']);
            }
        } catch (\Exception $e) {
            return redirect()
                ->route('verify.age')
                ->withErrorVerification($e->getMessage());
        }
    }

    public function webhook(Request $request): RedirectResponse
    {
        try {
            $payload = $request->all();

            $status = $payload['status'] ?? null;
            $sessionId = $payload['verificationSessionId'] ?? null;

            $validateSession = $this->validateSession($sessionId);

            $user = User::find($request->id);

            if ($user && $status && $validateSession) {
                switch ($status) {
                    case 'Approved':
                        $user->age_verification = 1;
                        Notifications::send($user->id, 1, 37, $user->id);
                        break;

                    case 'In Review':
                        $user->age_verification = 2;
                        break;

                    case 'Declined':
                        $user->age_verification = 3;
                        Notifications::send($user->id, 1, 38, $user->id);
                        break;
                }
                $user->save();
            }
            return redirect()->route('verify.age');
        } catch (\Exception $e) {
            Log::error('Error processing Didit webhook: ' . $e->getMessage());
            return redirect()
                ->route('verify.age')
                ->withErrorVerification($e->getMessage());
        }
    }

    private function validateSession(string $sessionId): bool
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $this->baseUrl . '/v2/session/' . $sessionId . '/decision/', [
                'headers' => [
                    'accept' => 'application/json',
                    'x-api-key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['status'] == 'Approved') {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Didit error validateSession', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new \Exception($e->getMessage());
        }
    }

    public function resultAgeVerification(Request $request): RedirectResponse {}
}
