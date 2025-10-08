<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/explore/front', name: 'app_explore_front')]
    public function exploreFront(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $username = null;
        if ($user instanceof User && $user->getProfile()) {
            $username = $user->getProfile()->getUsername();
        }

        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Count total posts
        $totalPosts = $entityManager->getRepository(\App\Entity\Post::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.user', 'u')
            ->where('p.isVisible = :visible')
            ->andWhere('u.isVerified = :verified')
            ->setParameter('visible', true)
            ->setParameter('verified', true)
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = (int) ceil($totalPosts / $limit);

        // Show posts from verified users
        $posts = $entityManager->getRepository(\App\Entity\Post::class)
            ->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('p.isVisible = :visible')
            ->andWhere('u.isVerified = :verified')
            ->setParameter('visible', true)
            ->setParameter('verified', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->render('explore/front.html.twig', [
            'user' => $user,
            'username' => $username,
            'posts' => $posts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'routeName' => 'app_explore_front',
            'routeParams' => []
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/explore/job', name: 'app_explore_job')]
    public function exploreJob(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $username = null;
        if ($user instanceof User && $user->getProfile()) {
            $username = $user->getProfile()->getUsername();
        }

        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // Count total job offers
        $totalJobOffers = $entityManager->getRepository(\App\Entity\JobOffer::class)
            ->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->join('j.company', 'c')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = (int) ceil($totalJobOffers / $limit);

        // Get all job offers with their companies
        $jobOffers = $entityManager->getRepository(\App\Entity\JobOffer::class)
            ->createQueryBuilder('j')
            ->join('j.company', 'c')
            ->orderBy('j.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->render('explore/job.html.twig', [
            'username' => $username,
            'jobOffers' => $jobOffers,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'routeName' => 'app_explore_job',
            'routeParams' => []
        ]);
    }
}
