<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class RegistrationStepListener
{
  private array $authRoutes = [
    'app_register',
    'app_login',
    'app_connection'
  ];

  private array $allowedRoutesForUnverified = [
    'app_register',
    'app_verify_email_pending',
    'app_verify_email',
    'app_logout',
    'app_home'
  ];

  private array $allowedRoutesForIncomplete = [
    'app_register',
    'app_verify_email_pending',
    'app_verify_email',
    'app_profile_complete',
    'app_logout',
    'app_home'
  ];

  public function __construct(
    private Security $security,
    private RouterInterface $router
  ) {}

  public function __invoke(RequestEvent $event): void
  {
    $request = $event->getRequest();
    $route = $request->attributes->get('_route');

    // Skip for public routes
    if (!$route || $request->isXmlHttpRequest() || !$event->isMainRequest()) {
      return;
    }

    $user = $this->security->getUser();

    if (!$user instanceof User) {
      return;
    }

    // If user is complete and tries to access auth routes, redirect to home
    if ($user->hasRole('ROLE_USER_COMPLETE') && in_array($route, $this->authRoutes)) {
      $response = new RedirectResponse($this->router->generate('app_home'));
      $event->setResponse($response);
      return;
    }

    // If user is not verified and not on allowed routes
    if (!$user->isVerified() && !in_array($route, $this->allowedRoutesForUnverified)) {
      $response = new RedirectResponse($this->router->generate('app_verify_email_pending'));
      $event->setResponse($response);
      return;
    }

    // If user is verified but profile not complete and not on allowed routes
    if ($user->isVerified() && !$user->isProfileComplete() && !in_array($route, $this->allowedRoutesForIncomplete)) {
      $response = new RedirectResponse($this->router->generate('app_profile_complete'));
      $event->setResponse($response);
      return;
    }
  }
}
