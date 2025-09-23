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

    $posts = [];
    $profiles = [];

    if (!empty($search)) {
      if ($type === 'all' || $type === 'post') {
        $posts = $entityManager->getRepository(Post::class)
          ->createQueryBuilder('p')
          ->join('p.user', 'u')
          ->join('u.profile', 'pr')
          ->where('p.description LIKE :search')
          ->setParameter('search', '%' . $search . '%')
          ->orderBy('p.createdAt', 'DESC')
          ->setMaxResults(20)
          ->getQuery()
          ->getResult();
      }

      if ($type === 'all' || $type === 'user') {
        $profiles = $entityManager->getRepository(Profile::class)
          ->createQueryBuilder('p')
          ->join('p.user', 'u')
          ->where('p.displayName LIKE :search OR p.username LIKE :search OR p.job LIKE :search OR p.location LIKE :search OR u.email LIKE :search')
          ->andWhere('u.id != :currentUserId')
          ->setParameter('search', '%' . $search . '%')
          ->setParameter('currentUserId', $user->getId())
          ->orderBy('p.displayName', 'ASC')
          ->setMaxResults(20)
          ->getQuery()
          ->getResult();
      }
    } else {
      // Afficher des posts et profils récents par défaut
      if ($type === 'all' || $type === 'post') {
        $posts = $entityManager->getRepository(Post::class)
          ->createQueryBuilder('p')
          ->join('p.user', 'u')
          ->join('u.profile', 'pr')
          ->orderBy('p.createdAt', 'DESC')
          ->setMaxResults(10)
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
          ->setMaxResults(10)
          ->getQuery()
          ->getResult();
      }
    }

    return $this->render('search/search.html.twig', [
      'search' => $search,
      'type' => $type,
      'posts' => $posts,
      'profiles' => $profiles,
    ]);
  }
}
