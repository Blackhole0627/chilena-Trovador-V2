<?php

namespace App\Services;

use App\Models\PaymentGateways;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CoinPaymentsService
{
    private $clientId;
    private $clientSecret;
    private $baseUrl;

    public function __construct()
    {
        $payment = PaymentGateways::query()
            ->select(['key', 'key_secret', 'base_url'])
            ->whereName('Coinpayments')
            ->firstOrFail();

        $this->clientId = $payment->key;
        $this->clientSecret = $payment->key_secret;
        $this->baseUrl = trim($payment->base_url, '/');
    }

    private function generateSignature($method, $url, $payload)
    {
        $isoDate = gmdate('Y-m-d\TH:i:s');
        $payloadMessage = $payload ? json_encode($payload) : '';

        $message = "\u{FEFF}" . $method . $url . $this->clientId . $isoDate . $payloadMessage;

        $signature = base64_encode(hash_hmac('sha256', $message, $this->clientSecret, true));

        return [
            'signature' => $signature,
            'timestamp' => $isoDate
        ];
    }

    private function makeRequest($method, $endpoint, $payload = null)
    {
        $url = $this->baseUrl . $endpoint;
        $auth = $this->generateSignature($method, $url, $payload);

        try {
            $request = Http::withHeaders([
                'X-CoinPayments-Client' => $this->clientId,
                'X-CoinPayments-Timestamp' => $auth['timestamp'],
                'X-CoinPayments-Signature' => $auth['signature'],
                'Content-Type' => 'application/json',
            ]);

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $payload),
                'PUT' => $request->put($url, $payload),
                'DELETE' => $request->delete($url),
                default => throw new \Exception('Invalid HTTP method')
            };

            if ($response->failed()) {
                $error = $response->json();
                throw new \Exception($error['message'] ?? 'API request failed');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('CoinPayments API Error: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
                'method' => $method
            ]);
            throw $e;
        }
    }

    public function createInvoice(array $data)
    {
        $payload = [
            'currency' => $data['currency'],
            'amount' => [
                'total' => (string) $data['amount']['total'],
            ],
            'webhook' => [
                'notifications' => $data['webhook']['notifications'],
            ]

        ];

        if (isset($data['items'])) {
            $payload['items'] = $data['items'];
        }

        if (isset($data['description'])) {
            $payload['description'] = $data['description'];
        }

        if (isset($data['client_id'])) {
            $payload['clientId'] = $data['client_id'];
        }

        if (isset($data['invoice_id'])) {
            $payload['invoiceId'] = $data['invoice_id'];
        }

        if (isset($data['buyer'])) {
            $payload['buyer'] = $data['buyer'];
        }

        if (isset($data['is_email_delivery'])) {
            $payload['isEmailDelivery'] = $data['is_email_delivery'];
        }

        if (isset($data['email_delivery'])) {
            $payload['emailDelivery'] = $data['email_delivery'];
        }

        if (isset($data['due_date'])) {
            $payload['dueDate'] = $data['due_date'];
        }

        if (isset($data['invoice_date'])) {
            $payload['invoiceDate'] = $data['invoice_date'];
        }

        if (isset($data['draft'])) {
            $payload['draft'] = $data['draft'];
        }

        if (isset($data['shipping'])) {
            $payload['shipping'] = $data['shipping'];
        }

        if (isset($data['require_buyer_name_and_email'])) {
            $payload['requireBuyerNameAndEmail'] = $data['require_buyer_name_and_email'];
        }

        if (isset($data['buyer_data_collection_message'])) {
            $payload['buyerDataCollectionMessage'] = $data['buyer_data_collection_message'];
        }

        if (isset($data['notes'])) {
            $payload['notes'] = $data['notes'];
        }

        if (isset($data['notes_to_recipient'])) {
            $payload['notesToRecipient'] = $data['notes_to_recipient'];
        }

        if (isset($data['terms_and_conditions'])) {
            $payload['termsAndConditions'] = $data['terms_and_conditions'];
        }

        if (isset($data['merchant_options'])) {
            $payload['merchantOptions'] = $data['merchant_options'];
        }

        if (isset($data['custom_data'])) {
            $payload['customData'] = $data['custom_data'];
        }

        if (isset($data['metadata'])) {
            $payload['metadata'] = $data['metadata'];
        }

        if (isset($data['po_number'])) {
            $payload['poNumber'] = $data['po_number'];
        }

        if (isset($data['payout_overrides'])) {
            $payload['payoutOverrides'] = $data['payout_overrides'];
        }

        if (isset($data['use_coin_reservation'])) {
            $payload['useCoinReservation'] = $data['use_coin_reservation'];
        }

        if (isset($data['payment'])) {
            $payload['payment'] = $data['payment'];
        }

        if (isset($data['hide_shopping_cart'])) {
            $payload['hideShoppingCart'] = $data['hide_shopping_cart'];
        }

        if (isset($data['affiliate_id'])) {
            $payload['affiliateId'] = $data['affiliate_id'];
        }

        if (isset($data['is_simple_qr'])) {
            $payload['isSimpleQR'] = $data['is_simple_qr'];
        }

        if (isset($data['success_url'])) {
            $payload['successUrl'] = $data['success_url'];
        }

        if (isset($data['cancel_url'])) {
            $payload['cancelUrl'] = $data['cancel_url'];
        }

        return $this->makeRequest('POST', '/api/v2/merchant/invoices', $payload);
    }

    public function validateWebhook($request)
    {
        $clientId = $request->header('X-CoinPayments-Client');
        $timestamp = $request->header('X-CoinPayments-Timestamp');
        $signature = $request->header('X-CoinPayments-Signature');

        if ($clientId !== $this->clientId) {
            Log::warning('Invalid webhook client ID', ['received' => $clientId]);
            return false;
        }

        $requestTime = strtotime($timestamp);
        $currentTime = time();
        $timeDiff = abs($currentTime - $requestTime);

        if ($timeDiff > 300) {
            Log::warning('Webhook timestamp too old', ['diff' => $timeDiff]);
            return false;
        }

        $payload = $request->getContent();
        $method = 'POST';
        $url = $request->fullUrl();

        $message = "\u{FEFF}" . $method . $url . $clientId . $timestamp . $payload;
        $expectedSignature = base64_encode(hash_hmac('sha256', $message, $this->clientSecret, true));

        if ($signature !== $expectedSignature) {
            Log::warning('Invalid webhook signature');
            return false;
        }

        return true;
    }
}
