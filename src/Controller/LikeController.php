<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
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
  use UserRedirectionTrait;
  #[Route('/post/{id}/like', name: 'app_post_like', methods: ['GET', 'POST'])]
  public function toggleLike(Post $post, EntityManagerInterface $entityManager, Request $request): RedirectResponse
  {
    $redirectResponse = $this->checkUserAccess();
    if ($redirectResponse) {
      return $redirectResponse;
    }

    /** @var User $user */
    $user = $this->getUser();
    $profile = $user->getProfile();

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
