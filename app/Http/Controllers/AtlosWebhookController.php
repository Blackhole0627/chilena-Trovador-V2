<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Deposits;
use Illuminate\Http\Request;
use App\Models\PaymentGateways;
use Illuminate\Support\Facades\Http;

class AtlosWebhookController extends Controller
{
    use Traits\Functions;

    public function webhook(Request $request)
    {
        $payment = PaymentGateways::whereName('Atlos')->firstOrFail();
        $signature = $request->header('signature');
        $payload = $request->getContent();
        $apiSecret = $payment->key_secret;

        // Verify the HMAC-SHA256 signature
        $calculatedHash = base64_encode(hash_hmac('sha256', $payload, $apiSecret, true));

        if ($signature !== $calculatedHash) {
            info('ATLOS Webhook: Signature mismatch. Received ' . $signature . ' Expected ' . $calculatedHash);
            return response('Invalid signature', 401);
        }

        $response = json_decode($payload, true);

        info('ATLOS Webhook received:', $response);

        // Process the webhook based on the status
        if ($response['Status'] == 100) {
            if (!$this->confirmPaymentByHash($response['BlockchainHash'])) {
                info('Invalid blockchain hash (from ATLOS Webhook)');
                return response('Invalid blockchain hash', 403);
            }

            $dataDecode = base64_decode($response['OrderId']);
            parse_str($dataDecode, $data);

            $txnId = $response['TransactionId'];

            if (Deposits::where('txn_id', $txnId)->doesntExist()) {
                $this->deposit(
                    $data['user'],
                    $txnId,
                    $data['amount'],
                    'Atlos',
                    $data['taxes'] ?? null
                );

                // Add Funds to User
                User::find($data['user'])->increment('wallet', $data['amount']);
            }

            info("Payment confirmed for the order {$txnId} (From ATLOS Webhook)");
        } else {
            info("Payment status not confirmed for order {$txnId}: Status {$response['Status']} (From ATLOS Webhook)");
        }

        return response('OK', 200);
    }

    protected function confirmPaymentByHash(string $blockchainHash)
    {
        $payment = PaymentGateways::whereName('Atlos')->firstOrFail();
        $merchantId = $payment->key;
        $apiSecret = $payment->key_secret;

        $url = 'https://atlos.io/api/Transaction/FindByHash';

        $data = [
            'MerchantId' => $merchantId,
            'BlockchainHash' => $blockchainHash,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'ApiSecret' => $apiSecret
        ])
            ->timeout(60)
            ->retry(5)
            ->post($url, $data);

        if ($response->failed()) {
            info('Error querying ATLOS (From confirmPaymentByHash): ' . $response->body());
            return false;
        }

        $result = $response->json();

        if (!$result['IsFound']) {
            info("Transaction not found (From confirmPaymentByHash): $blockchainHash");
            return false;
        }

        $transaction = $result['Transaction'];

        if ($transaction['Status'] == 100) {
            info("Payment confirmed for the order {$transaction['TransactionId']} (From confirmPaymentByHash) ");
            return true;
        } else {
            info("Payment status not confirmed for order {$transaction['TransactionId']}: Status {$transaction['Status']} (From confirmPaymentByHash)");
            return false;
        }
    }
}
