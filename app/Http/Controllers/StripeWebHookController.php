<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Plans;
use App\Models\Deposits;
use Laravel\Cashier\Cashier;
use App\Models\Notifications;
use Illuminate\Http\Response;
use App\Models\PaymentGateways;
use Laravel\Cashier\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Http\Controllers\WebhookController;

class StripeWebHookController extends WebhookController
{
  use Traits\Functions;

  /**
   *
   * customer.subscription.deleted
   *
   * @param array $payload
   * @return Response|\Symfony\Component\HttpFoundation\Response
   */
  public function handleCustomerSubscriptionDeleted(array $payload)
  {
    $user = $this->getUserByStripeId($payload['data']['object']['customer']);
    if ($user) {
      $user->subscriptions->filter(function ($subscription) use ($payload) {
        return $subscription->stripe_id === $payload['data']['object']['id'];
      })->each(function ($subscription) {
        $subscription->markAsCanceled();
      });
    }
    return new Response('Webhook Handled', 200);
  }

  /**
   *
   * WEBHOOK Insert the information of each payment in the Payments table when successfully generating an invoice in Stripe
   *
   * @param array $payload
   * @return Response|\Symfony\Component\HttpFoundation\Response
   */
  public function handleInvoicePaymentSucceeded($payload)
  {
    try {
      $data     = $payload['data'];
      $object   = $data['object'];
      $customer = $object['customer'];
      $amount   = in_array(config('settings.currency_code'), config('currencies.zero-decimal')) ? $object['subtotal'] : ($object['subtotal'] / 100);
      $user     = $this->getUserByStripeId($customer);
      $interval = $object['lines']['data'][0]['metadata']['interval'] ?? 'monthly';
      $creatorId = $object['lines']['data'][0]['metadata']['creator_id'] ?? null;
      $taxes    = $object['lines']['data'][0]['metadata']['taxes'] ?? null;

      $subscriptionId = $object['subscription'] ?? $object['lines']['data'][0]['parent']['subscription_item_details']['subscription'] ?? null;


      if ($user && $subscriptionId && $creatorId) {
        $subscription = Subscription::whereStripeId($subscriptionId)->first();
        if ($subscription) {

          // Get creator
          $getCreator = Plans::with(['creator:id,status,verified_id,custom_fee,balance'])->whereName($subscription->stripe_price)->first();

          if ($getCreator) {
            $creator = $getCreator->creator;
          } else {
            $creator = User::whereId($creatorId)
            ->select(['id', 'status', 'verified_id', 'custom_fee', 'balance'])
            ->where('status', 'active')
            ->where('verified_id', 'yes')
            ->firstOrFail();
          }

          $subscription->stripe_status = 'active';
          $subscription->creator_id = $creator->id;
          $subscription->interval = $interval;
          $subscription->save();

          // Get Payment Gateway
          $payment = PaymentGateways::whereName('Stripe')->firstOrFail();
          // Admin and user earnings calculation
          $earnings = $this->earningsAdminUser($creator->custom_fee, $amount, $payment->fee, $payment->fee_cents);

          // Insert Transaction
          $this->transaction(
            $object['id'],
            $subscription->user_id,
            $subscription->id,
            $creator->id,
            $amount,
            $earnings['user'],
            $earnings['admin'],
            'Stripe',
            'subscription',
            $earnings['percentageApplied'],
            $taxes ?? null
          );

          // Add Earnings to User
          $creator->increment('balance', $earnings['user']);

          // Send Notification to user
          if ($object['billing_reason'] == 'subscription_cycle') {
            // Notify to user - destination, author, type, target
            Notifications::send($creator->id, $subscription->user_id, 12, $subscription->user_id);
          }
        }
        return new Response('Webhook Handled: {handleInvoicePaymentSucceeded}', 200);
      }
      return new Response('Webhook Handled but user not found: {handleInvoicePaymentSucceeded}', 200);
    } catch (\Exception $exception) {
      Log::debug($exception->getMessage());
      return new Response('Webhook Unhandled: {handleInvoicePaymentSucceeded}', $exception->getCode());
    }
  }

  /**
   *
   * checkout.session.completed
   *
   * @param array $payload
   * @return Response|\Symfony\Component\HttpFoundation\Response
   */
  public function handleCheckoutSessionCompleted($payload)
  {
    try {
      $data     = $payload['data'];
      $object   = $data['object'];
      $user     = $object['metadata']['user'] ?? null;
      $amount   = $object['metadata']['amount'] ?? null;
      $taxes    = $object['metadata']['taxes'] ?? null;
      $type     = $object['metadata']['type'] ?? null;

      if (! isset($type)) {
        return new Response('Webhook Handled with error: type transaction not defined', 500);
      }

      // Add funds (Deposit)
      if (isset($type) && $type == 'deposit') {
        if ($object['payment_status'] == 'paid' && isset($user)) {
          $amount_total = in_array(config('settings.currency_code'), config('currencies.zero-decimal')) ? $object['amount_total'] : $object['amount_total'] / 100;
          if (isset($amount) && $amount_total >= $amount) {
            // Check transaction
            $verifiedTxnId = Deposits::where('txn_id', $object['payment_intent'])->first();
            if (! $verifiedTxnId) {
              // Insert Deposit
              $this->deposit($user, $object['payment_intent'], $amount, 'Stripe', $taxes);

              // Add Funds to User
              User::find($user)->increment('wallet', $amount);
            }
          }
        }
      }

      return new Response('Webhook Handled: {handleInvoicePaymentSucceeded}', 200);
    } catch (\Exception $exception) {
      Log::debug($exception->getMessage());
      return new Response('Webhook Unhandled: {handleInvoicePaymentSucceeded}', $exception->getCode());
    }
  }

  /**
   *
   * charge.refunded
   *
   * @param array $payload
   * @return Response|\Symfony\Component\HttpFoundation\Response
   */
  public function handleChargeRefunded($payload)
  {
    try {
      $subscriptionId = $payload['data']['object']['subscription'] ?? $payload['lines']['data'][0]['parent']['subscription_item_details']['subscription'] ?? null;
      $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
      $stripe->subscriptions->cancel($subscriptionId, []);

      return new Response('Webhook Handled: {handleChargeRefunded}', 200);
    } catch (\Exception $exception) {
      Log::debug("Exception Webhook {handleChargeRefunded}: " . $exception->getMessage() . ", Line: " . $exception->getLine() . ', File: ' . $exception->getFile());
      return new Response('Webhook Handled with error: {handleChargeRefunded}', 400);
    }
  }

  /**
   * WEBHOOK Manage the SCA by notifying the user by email
   *
   * @param  array  $payload
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function handleInvoicePaymentActionRequired(array $payload)
  {
    $subscriptionId = $payload['data']['object']['subscription'] ?? $payload['lines']['data'][0]['parent']['subscription_item_details']['subscription'] ?? null;
    $subscription = Subscription::whereStripeId($subscriptionId)->first();
    if ($subscription) {
      $subscription->stripe_status = "incomplete";
      $subscription->last_payment = $payload['data']['object']['payment_intent'];
      $subscription->save();
    }

    if (is_null($notification = config('cashier.payment_notification'))) {
      return $this->successMethod();
    }

    if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
      if (in_array(Notifiable::class, class_uses_recursive($user))) {
        $payment = new \Laravel\Cashier\Payment(Cashier::stripe()->paymentIntents->retrieve(
          $payload['data']['object']['payment_intent']
        ));

        $user->notify(new $notification($payment));
      }
    }
    return $this->successMethod();
  }
}
