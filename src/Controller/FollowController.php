<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FollowController extends AbstractController
{
  use UserRedirectionTrait;
  #[Route('/profile/{username}/follow', name: 'app_profile_follow', methods: ['GET', 'POST'])]
  public function toggleFollow(string $username, EntityManagerInterface $entityManager, Request $request): RedirectResponse
  {
    $redirectResponse = $this->checkUserAccess();
    if ($redirectResponse) {
      return $redirectResponse;
    }

    /** @var User $user */
    $user = $this->getUser();
    $currentProfile = $user->getProfile();

    // Profile to follow
    $profileToFollow = $entityManager->getRepository(Profile::class)->findOneBy(['username' => $username]);

    if (!$profileToFollow) {
      $this->addFlash('error', 'Profil introuvable');
      return $this->redirect($request->headers->get('referer') ?: '/');
    }

    // Prevent self-following
    if ($currentProfile === $profileToFollow) {
      $this->addFlash('error', 'Vous ne pouvez pas vous suivre vous-même');
      return $this->redirect($request->headers->get('referer') ?: '/');
    }

    $isFollowing = $currentProfile->isFollowing($profileToFollow);

    if ($isFollowing) {
      $currentProfile->removeFollowing($profileToFollow);
      $this->addFlash('success', 'Vous ne suivez plus ' . $profileToFollow->getDisplayName());
    } else {
      $currentProfile->addFollowing($profileToFollow);
      $this->addFlash('success', 'Vous suivez maintenant ' . $profileToFollow->getDisplayName());
    }

    $entityManager->flush();

    // Rediriger vers la page précédente
    return $this->redirect($request->headers->get('referer') ?: '/');
  }
}
