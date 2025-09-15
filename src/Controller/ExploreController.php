<?php

namespace App\Controller;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ExploreController extends AbstractController
{
    #[Route(['/', '/explore'], name: 'app_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_explore_front');
    }

    #[Route('/explore/front', name: 'app_explore_front')]
    public function exploreFront(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $posts = $entityManager->getRepository(\App\Entity\Post::class)->findBy(
            ['isVisible' => true],
            ['createdAt' => 'DESC']
        );

        return $this->render('explore/front.html.twig', [
            'user' => $user,
            'posts' => $posts
        ]);
    }

    #[Route('/explore/job', name: 'app_explore_job')]
    public function exploreJob(): Response
    {
        return $this->render('explore/job.html.twig');
    }
}
