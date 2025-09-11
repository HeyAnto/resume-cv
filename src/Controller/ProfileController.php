<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile')]
final class ProfileController extends AbstractController
{
    use UserRedirectionTrait;

    #[Route('', name: 'app_profile_redirect')]
    public function redirectToUserProfile(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user->getProfile()?->getUsername()) {
            return $this->redirectToRoute('app_complete');
        }

        return $this->redirectToRoute('app_profile', ['username' => $user->getProfile()->getUsername()]);
    }

    #[Route('/{username}', name: 'app_profile')]
    public function profile(string $username): Response
    {
        return $this->handleProfileAction($username, 'profile/profile.html.twig');
    }

    #[Route('/{username}/posts', name: 'app_profile_posts')]
    public function profilePosts(string $username): Response
    {
        return $this->handleProfileAction($username, 'profile/profile-posts.html.twig');
    }

    #[Route('/{username}/edit', name: 'app_profile_edit')]
    public function profileEdit(string $username): Response
    {
        $redirectResponse = $this->checkUserAccess();
        if ($redirectResponse) {
            return $redirectResponse;
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($user->getProfile()->getUsername() !== $username) {
            return $this->redirectToRoute('app_profile', ['username' => $user->getProfile()->getUsername()]);
        }

        return $this->render('profile/profile-edit.html.twig', ['username' => $username]);
    }

    private function handleProfileAction(string $username, string $template, bool $requireOwnership = false): Response
    {
        $redirectResponse = $this->checkUserAccess();
        if ($redirectResponse) {
            return $redirectResponse;
        }

        return $this->render($template, ['username' => $username]);
    }
}
