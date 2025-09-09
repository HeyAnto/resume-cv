<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExploreController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_explore_front');
    }

    #[Route('/explore', name: 'app_explore')]
    public function explore(): Response
    {
        return $this->redirectToRoute('app_explore_front');
    }

    #[Route('/explore/front', name: 'app_explore_front')]
    public function exploreFront(): Response
    {
        return $this->render('explore/front.html.twig');
    }
}
