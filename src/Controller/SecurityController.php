<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/connection', name: 'app_connection')]
    public function connection(): Response
    {
        // If user is already authenticated, redirect based on their status
        if ($this->getUser()) {
            return $this->redirectBasedOnUserStatus();
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/connection/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already authenticated, redirect based on their status
        if ($this->getUser()) {
            return $this->redirectBasedOnUserStatus();
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = new User();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    private function redirectBasedOnUserStatus(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // If user has complete profile
        if ($user->hasRole('ROLE_USER_COMPLETE')) {
            return $this->redirectToRoute('app_home');
        }

        // If user is verified but profile incomplete
        if ($user->isVerified() && !$user->isProfileComplete()) {
            return $this->redirectToRoute('app_profile_complete');
        }

        // If user is not verified
        if (!$user->isVerified()) {
            return $this->redirectToRoute('app_verify_email_pending');
        }

        // Default fallback
        return $this->redirectToRoute('app_home');
    }
}
