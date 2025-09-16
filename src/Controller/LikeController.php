<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LikeController extends AbstractController
{
  #[Route('/post/{id}/like', name: 'app_post_like', methods: ['GET', 'POST'])]
  public function toggleLike(Post $post, EntityManagerInterface $entityManager, Request $request): RedirectResponse
  {
    /** @var User $user */
    $user = $this->getUser();

    if (!$user) {
      $this->addFlash('error', 'Vous devez être connecté pour liker un post');
      return $this->redirectToRoute('app_login');
    }

    if (!$user->isVerified()) {
      $this->addFlash('error', 'Votre compte doit être vérifié pour liker un post');
      return $this->redirectToRoute('app_login');
    }

    $profile = $user->getProfile();

    if (!$profile) {
      $this->addFlash('error', 'Vous devez compléter votre profil pour liker un post');
      return $this->redirectToRoute('app_profile_edit');
    }

    $isLiked = $post->isLikedBy($profile);

    if ($isLiked) {
      $post->unlikeBy($profile);
    } else {
      $post->likeBy($profile);
    }

    $entityManager->flush();

    return $this->redirect($request->headers->get('referer') ?: '/');
  }
}
