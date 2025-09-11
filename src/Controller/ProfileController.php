<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    use UserRedirectionTrait;

    #[Route('/profile', name: 'app_profile_redirect')]
    public function redirectToUserProfile(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user instanceof User || !$user->getProfile() || !$user->getProfile()->getUsername()) {
            return $this->redirectToRoute('app_complete');
        }

        return $this->redirectToRoute('app_profile', ['username' => $user->getProfile()->getUsername()]);
    }

    #[Route('/profile/{username}', name: 'app_profile')]
    public function profile(string $username): Response
    {
        $redirectResponse = $this->checkUserAccess();
        if ($redirectResponse !== null) {
            return $redirectResponse;
        }

        return $this->render('profile/profile.html.twig', [
            'username' => $username,
        ]);
    }

    #[Route('/profile/{username}/posts', name: 'app_profile_posts')]
    public function profilePosts(string $username): Response
    {
        $redirectResponse = $this->checkUserAccess();
        if ($redirectResponse !== null) {
            return $redirectResponse;
        }

        return $this->render('profile/profile-posts.html.twig', [
            'username' => $username,
        ]);
    }
}
