<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\User;
use App\Form\EmailFormType;
use App\Form\UsernameFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/profile')]
final class AccountEditController extends AbstractController
{
    use UserRedirectionTrait;
    private function checkUsernameAccess(string $username): ?Response
    {
        // Check user access first
        $userCheck = $this->checkUserAccess();
        if ($userCheck) {
            return $userCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        // Redirect to public profile
        if ($profile->getUsername() !== $username) {
            return $this->redirectToRoute('app_profile', ['username' => $username]);
        }

        return null;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/account', name: 'app_account_edit')]
    public function accountEdit(Request $request, EntityManagerInterface $entityManager, string $username): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

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
            'username' => $username,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/account/delete-account', name: 'app_account_delete', methods: ['POST'])]
    public function deleteAccount(string $username, EntityManagerInterface $entityManager, Request $request, TokenStorageInterface $tokenStorage): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        // Delete profile picture
        $profilePicturePath = $profile->getProfilePicturePath();
        if ($profilePicturePath && $profilePicturePath !== 'images/img_default_user.webp') {
            $pictureFile = $this->getParameter('kernel.project_dir') . '/public/' . $profilePicturePath;
            if (file_exists($pictureFile)) {
                unlink($pictureFile);
            }
        }

        // Disconnect user
        $tokenStorage->setToken(null);

        // Invalidate session
        $request->getSession()->invalidate();

        // Remove user
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_login');
    }
}
