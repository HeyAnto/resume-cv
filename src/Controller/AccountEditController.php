<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EmailFormType;
use App\Form\UsernameFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile')]
final class AccountEditController extends AbstractController
{
    #[Route('/{username}/account', name: 'app_account_edit')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        $usernameForm = $this->createForm(UsernameFormType::class, $profile);
        $emailForm = $this->createForm(EmailFormType::class, $user);

        // Store original username
        $originalUsername = $profile->getUsername();

        $usernameForm->handleRequest($request);
        $emailForm->handleRequest($request);

        if ($usernameForm->isSubmitted()) {
            // Skip validation unchanged
            $usernameChanged = $profile->getUsername() !== $originalUsername;

            if (!$usernameChanged || $usernameForm->isValid()) {
                if ($usernameChanged) {
                    $this->addFlash('success', 'Username updated successfully');
                }
                $entityManager->flush();
            }
        }

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Email updated successfully');
        }

        return $this->render('profile-edit/account-edit.html.twig', [
            'usernameForm' => $usernameForm->createView(),
            'emailForm' => $emailForm->createView(),
        ]);
    }
}
