<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

#[AsEventListener(event: 'kernel.exception')]
final class NotFoundExceptionListener
{
  public function __construct(
    private Environment $twig
  ) {}

  public function onKernelException(ExceptionEvent $event): void
  {
    $exception = $event->getThrowable();

    if (!$exception instanceof NotFoundHttpException) {
      return;
    }

    $content = $this->twig->render('notfound.html.twig');
    $response = new Response($content, 404);

    $event->setResponse($response);
  }
}
