<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\TwoFactorCodes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorAuthController extends Controller
{
	use Traits\Functions;

	public function verify(Request $request)
	{
		$isProfileTwoFA = $request->isProfileTwoFA;

		$messages = [
			'code.required' => trans('general.please_enter_code')
		];

		$validator = Validator::make($request->all(), [
			'code' => 'required'
		], $messages);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->getMessageBag()->toArray()
			]);
		}

		$verifyCode = TwoFactorCodes::whereUserId(session('user:id'))
			->where('code', $request->code)
			->where('updated_at', '>=', now()->subMinutes(2))
			->first();

		if ($verifyCode) {

			// Delete old code
			TwoFactorCodes::whereUserId(session('user:id'))->delete();

			// Login user
			auth()->loginUsingId(session()->pull('user:id'), true);

			// Insert Login Session
			$this->loginSession(auth()->id());

			return response()->json([
				'success' => true,
				'isProfileTwoFA' => $isProfileTwoFA ? true : false,
				'redirect' => url('/')
			]);
		}

		return response()->json([
			'success' => false,
			'errors' => ['error' => trans('general.code_2fa_invalid')]
		]);
	} // End method

	/**
	 * Resend code
	 */
	public function resend()
	{
		if (RateLimiter::tooManyAttempts('resend_2fa_code', 3)) {
			return response()->json([
				'success' => false,
				'errors' => ['error' => __('general.error_twofa_too_many_attempts')]
			]);
		}

		// Delete old code
		TwoFactorCodes::whereUserId(session('user:id'))->delete();

		// Get User details
		$user = User::findOrFail(session('user:id'));

		$this->generateTwofaCode($user);

		RateLimiter::hit('resend_2fa_code');

		return response()->json([
			'success' => true,
			'text' => trans('general.resend_code_success')
		]);
	}
}
