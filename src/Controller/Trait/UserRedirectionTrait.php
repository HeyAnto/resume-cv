<?php

namespace App\Controller\Trait;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

trait UserRedirectionTrait
{
  private function redirectBasedOnUserStatus(): Response
  {
    $user = $this->getUser();

    if (!$user instanceof User) {
      return $this->redirectToRoute('app_register');
    }

    // If user has complete profile, go to home
    if ($user->hasRole('ROLE_USER_COMPLETE')) {
      return $this->redirectToRoute('app_home');
    }

    // If user is verified but profile incomplete, go to profile completion
    if ($user->isVerified() && !$user->isProfileComplete()) {
      return $this->redirectToRoute('app_profile_complete');
    }

    // If user is not verified, go to email verification
    if (!$user->isVerified()) {
      return $this->redirectToRoute('app_verify_email_pending');
    }

    // Default fallback
    return $this->redirectToRoute('app_home');
  }
}
