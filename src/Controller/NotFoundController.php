<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NotFoundController extends AbstractController
{
  #[Route('/404', name: 'app_not_found')]
  public function notFound(): Response
  {
    return $this->render('notfound.html.twig');
  }
}
