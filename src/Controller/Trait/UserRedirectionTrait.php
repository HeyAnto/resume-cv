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

    if ($user->hasRole('ROLE_USER_COMPLETE')) {
      return $this->redirectToRoute('app_home');
    }

    if ($user->isVerified() && !$user->isProfileComplete()) {
      return $this->redirectToRoute('app_complete');
    }

    if (!$user->isVerified()) {
      return $this->redirectToRoute('app_verify_email_pending');
    }

    return $this->redirectToRoute('app_home');
  }

  private function checkAuthAccess(): ?Response
  {
    if ($this->getUser()) {
      return $this->redirectBasedOnUserStatus();
    }
    return null;
  }

  private function checkUserAccess(): ?Response
  {
    $user = $this->getUser();

    if (!$user instanceof User) {
      return $this->redirectToRoute('app_register');
    }

    if (!$user->isVerified()) {
      return $this->redirectToRoute('app_verify_email_pending');
    }

    if (!$user->isProfileComplete()) {
      return $this->redirectToRoute('app_complete');
    }

    return null;
  }
}
