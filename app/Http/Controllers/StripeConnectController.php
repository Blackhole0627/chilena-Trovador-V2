<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use Stripe\Account;
use App\Models\User;
use Stripe\StripeClient;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class StripeConnectController extends Controller
{
    public function redirectToStripe()
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $user = auth()->user();

        // Complete the onboarding process
        if (!$user->completed_stripe_onboarding) {
            $token = Str::uuid() . uniqid() . auth()->id();

            DB::table('stripe_state_tokens')->insert([
                'created_at' => now(),
                'updated_at' => now(),
                'user_id'  => $user->id,
                'token'    => $token
            ]);

            try {
                // Let's check if they have a stripe connect id
                if (is_null($user->stripe_connect_id)) {
                    // Create account
                    $account = $stripe->accounts->create([
                        'country' => $user->country()->country_code,
                        'type'    => 'express',
                        'email'   => $user->email,
                    ]);

                    $user->update(['stripe_connect_id' => $account->id]);
                    $user->fresh();
                }

                $onboardLink = $stripe->accountLinks->create([
                    'account'     => $user->stripe_connect_id,
                    'refresh_url' => route('redirect.stripe'),
                    'return_url'  => route('save.stripe', ['token' => $token]),
                    'type'        => 'account_onboarding'
                ]);

                return redirect($onboardLink->url);
            } catch (\Exception $exception) {
                return back()->withError($exception->getMessage());
            }
        }

        try {
            $loginLink = $stripe->accounts->createLoginLink($user->stripe_connect_id);

            return redirect($loginLink->url);
        } catch (\Exception $exception) {
            return back()->withError($exception->getMessage());
        }
    }


    public function saveStripeAccount($token)
    {
        $stripeToken = DB::table('stripe_state_tokens')
            ->where('token', $token)
            ->first();

        abort_if(!$stripeToken, 404);

        $user = User::find($stripeToken->user_id);

        Stripe::setApiKey(config('services.stripe.secret'));

        $account = Account::retrieve($user->stripe_connect_id);

        DB::table('stripe_state_tokens')
            ->where('token', $token)
            ->delete();

        // Check the account status
        if ($account->details_submitted) {
            $user->update([
                'completed_stripe_onboarding' => true
            ]);
            
            return redirect('settings/payout/method')->with(['status' => __('general.stripe_connect_setup_success')]);
        }

        return redirect('settings/payout/method')->with(['error' => __('general.stripe_connect_setup_incomplete')]);
    }
}
