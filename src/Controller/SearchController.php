<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Post;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
  use UserRedirectionTrait;
  #[Route('/search', name: 'app_search', methods: ['GET'])]
  public function search(Request $request, EntityManagerInterface $entityManager): Response
  {
    $redirectResponse = $this->checkUserAccess();
    if ($redirectResponse) {
      return $redirectResponse;
    }

    /** @var User $user */
    $user = $this->getUser();
    $currentProfile = $user->getProfile();

    $search = $request->query->get('search', '');
    $type = $request->query->get('type', 'all');
    $page = max(1, $request->query->getInt('page', 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $posts = [];
    $profiles = [];
    $totalPages = 1;

    if (!empty($search)) {
      if ($type === 'all' || $type === 'post') {
        // Count total posts
        $totalPosts = $entityManager->getRepository(Post::class)
          ->createQueryBuilder('p')
          ->select('COUNT(p.id)')
          ->join('p.user', 'u')
          ->join('u.profile', 'pr')
          ->where('p.description LIKE :search')
          ->setParameter('search', '%' . $search . '%')
          ->getQuery()
          ->getSingleScalarResult();

        if ($type === 'post') {
          $totalPages = (int) ceil($totalPosts / $limit);
        }

        $posts = $entityManager->getRepository(Post::class)
          ->createQueryBuilder('p')
          ->join('p.user', 'u')
          ->join('u.profile', 'pr')
          ->where('p.description LIKE :search')
          ->setParameter('search', '%' . $search . '%')
          ->orderBy('p.createdAt', 'DESC')
          ->setMaxResults($limit)
          ->setFirstResult($offset)
          ->getQuery()
          ->getResult();
      }

      if ($type === 'all' || $type === 'user') {
        // Count total profiles
        $totalProfiles = $entityManager->getRepository(Profile::class)
          ->createQueryBuilder('p')
          ->select('COUNT(p.id)')
          ->join('p.user', 'u')
          ->where('p.displayName LIKE :search OR p.username LIKE :search OR p.job LIKE :search OR p.location LIKE :search OR u.email LIKE :search')
          ->andWhere('u.id != :currentUserId')
          ->setParameter('search', '%' . $search . '%')
          ->setParameter('currentUserId', $user->getId())
          ->getQuery()
          ->getSingleScalarResult();

        if ($type === 'user') {
          $totalPages = (int) ceil($totalProfiles / $limit);
        }

        $profiles = $entityManager->getRepository(Profile::class)
          ->createQueryBuilder('p')
          ->join('p.user', 'u')
          ->where('p.displayName LIKE :search OR p.username LIKE :search OR p.job LIKE :search OR p.location LIKE :search OR u.email LIKE :search')
          ->andWhere('u.id != :currentUserId')
          ->setParameter('search', '%' . $search . '%')
          ->setParameter('currentUserId', $user->getId())
          ->orderBy('p.displayName', 'ASC')
          ->setMaxResults($limit)
          ->setFirstResult($offset)
          ->getQuery()
          ->getResult();
      }

      // For 'all' type, calculate total pages based on combined results
      if ($type === 'all') {
        $totalResults = count($posts) + count($profiles);
        $totalPages = (int) ceil($totalResults / $limit);
      }
    } else {
      // Afficher des posts et profils récents par défaut
      if ($type === 'all' || $type === 'post') {
        $posts = $entityManager->getRepository(Post::class)
          ->createQueryBuilder('p')
          ->join('p.user', 'u')
          ->join('u.profile', 'pr')
          ->orderBy('p.createdAt', 'DESC')
          ->setMaxResults($limit)
          ->setFirstResult($offset)
          ->getQuery()
          ->getResult();
      }

      if ($type === 'all' || $type === 'user') {
        $profiles = $entityManager->getRepository(Profile::class)
          ->createQueryBuilder('p')
          ->join('p.user', 'u')
          ->where('u.id != :currentUserId')
          ->setParameter('currentUserId', $user->getId())
          ->orderBy('p.id', 'DESC')
          ->setMaxResults($limit)
          ->setFirstResult($offset)
          ->getQuery()
          ->getResult();
      }
    }

    return $this->render('search/search.html.twig', [
      'search' => $search,
      'type' => $type,
      'posts' => $posts,
      'profiles' => $profiles,
      'currentPage' => $page,
      'totalPages' => $totalPages,
      'routeName' => 'app_search',
      'routeParams' => array_filter([
        'search' => $search,
        'type' => $type
      ])
    ]);
  }
}
