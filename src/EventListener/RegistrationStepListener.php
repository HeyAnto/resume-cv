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
  private array $authRoutes = ['app_register', 'app_login', 'app_connection'];
  private array $publicRoutes = ['app_index', 'app_explore', 'app_explore_front', 'app_logout'];

  public function __construct(
    private Security $security,
    private RouterInterface $router
  ) {}

  public function __invoke(RequestEvent $event): void
  {
    $request = $event->getRequest();
    $route = $request->attributes->get('_route');

    if (!$route || $request->isXmlHttpRequest() || !$event->isMainRequest()) {
      return;
    }

    $user = $this->security->getUser();

    if (!$user instanceof User) {
      return;
    }

    // Complete users can't access auth routes
    if ($user->hasRole('ROLE_USER_COMPLETE') && in_array($route, $this->authRoutes)) {
      $event->setResponse(new RedirectResponse($this->router->generate('app_index')));
      return;
    }

    // Skip public routes
    if (in_array($route, $this->publicRoutes)) {
      return;
    }

    // Unverified users redirected to verification
    if (!$user->isVerified() && !in_array($route, [...$this->authRoutes, 'app_verify_email_pending', 'app_verify_email'])) {
      $event->setResponse(new RedirectResponse($this->router->generate('app_verify_email_pending')));
      return;
    }

    // Verified but incomplete users redirected to profile completion
    if ($user->isVerified() && !$user->isProfileComplete() && !in_array($route, [...$this->authRoutes, 'app_complete'])) {
      $event->setResponse(new RedirectResponse($this->router->generate('app_complete')));
      return;
    }
  }
}
