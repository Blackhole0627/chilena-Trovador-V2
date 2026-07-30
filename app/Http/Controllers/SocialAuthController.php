<?php

namespace App\Http\Controllers;

use App\Services\SocialAccountService;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
  // Redirect function
  public function redirect($provider)
  {
    return Socialite::driver($provider)->redirect();
  }
  // Callback function
  public function callback(SocialAccountService $service, $provider)
  {
    try {
      $user = $service->createOrGetUser(Socialite::driver($provider)->user(), $provider);

      // Return Error missing Email User
      if (!isset($user->id)) {
        return $user;
      } else {
        auth()->login($user);
      }
    } catch (\Exception $e) {
      return redirect('login')->with(['error_social_login' => $e->getMessage()]);
    }

    return redirect()->to('/');
  }
}
