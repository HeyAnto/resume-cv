<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    use UserRedirectionTrait;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/connection', name: 'app_connection')]
    public function connection(): Response
    {
        return $this->checkAuthAccess() ?? $this->redirectToRoute('app_login');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route(path: '/connection/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($redirect = $this->checkAuthAccess()) {
            return $redirect;
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
