<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExploreController extends AbstractController
{
    #[Route(['/', '/explore'], name: 'app_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_explore_front');
    }

    #[Route('/explore/front', name: 'app_explore_front')]
    public function exploreFront(): Response
    {
        return $this->render('explore/front.html.twig');
    }

    #[Route('/explore/job', name: 'app_explore_job')]
    public function exploreJob(): Response
    {
        return $this->render('explore/job.html.twig');
    }
}
