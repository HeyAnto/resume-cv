<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\User;
use App\Form\EmailFormType;
use App\Form\UsernameFormType;
use App\Repository\ProfileRepository;
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

        // Allow access
        if ($this->isGranted('ROLE_ADMIN') || $profile->getUsername() === $username) {
            return null;
        }

        // Redirect to public profile
        return $this->redirectToRoute('app_profile', ['username' => $username]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/account', name: 'app_account_edit')]
    public function accountEdit(Request $request, EntityManagerInterface $entityManager, string $username, ProfileRepository $profileRepository): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        // Get user and profile by username
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }
        $profile = $targetUser->getProfile();

        $usernameForm = $this->createForm(UsernameFormType::class, $profile);
        $emailForm = $this->createForm(EmailFormType::class, $targetUser);

        // Store original username
        $originalUsername = $profile->getUsername();

        $usernameForm->handleRequest($request);
        $emailForm->handleRequest($request);

        if ($usernameForm->isSubmitted()) {
            // Skip validation unchanged
            $usernameChanged = $profile->getUsername() !== $originalUsername;

            if (!$usernameChanged || $usernameForm->isValid()) {
                if ($usernameChanged) {
                    $profile->setUpdatedAt(new \DateTimeImmutable());
                    $entityManager->flush();
                    $this->addFlash('success', 'Username updated successfully');
                    return $this->redirectToRoute('app_account_edit', ['username' => $profile->getUsername()]);
                }
                $profile->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();
            }
        }

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $profile->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Email updated successfully');
        }

        return $this->render('profile-edit/account-edit.html.twig', [
            'usernameForm' => $usernameForm->createView(),
            'emailForm' => $emailForm->createView(),
            'username' => $username,
            'profile' => $profile,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/account/delete-account', name: 'app_account_delete', methods: ['POST'])]
    public function deleteAccount(string $username, EntityManagerInterface $entityManager, Request $request, TokenStorageInterface $tokenStorage, ProfileRepository $profileRepository): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        // Get user and profile by username
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }
        $profile = $targetUser->getProfile();

        // Delete all user-related images before deleting the account
        $this->deleteAllUserImages($targetUser);

        // Only disconnect if admin is deleting their own account
        if ($this->getUser() === $targetUser) {
            // Disconnect user
            $tokenStorage->setToken(null);
            // Invalidate session
            $request->getSession()->invalidate();
        }

        // Remove user
        $entityManager->remove($targetUser);
        $entityManager->flush();

        return $this->redirectToRoute('app_login');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/admin-delete', name: 'app_admin_delete_account', methods: ['POST'])]
    public function adminDeleteAccount(string $username, EntityManagerInterface $entityManager, ProfileRepository $profileRepository): Response
    {
        // Only admins
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        // Find user by username using ProfileRepository
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found.');
        }

        if ($this->getUser() === $targetUser) {
            $this->addFlash('error', 'You cannot delete your own account using this method.');
            return $this->redirectToRoute('app_profile', ['username' => $username]);
        }

        // Delete all user-related images before deleting the account
        $this->deleteAllUserImages($targetUser);

        // Remove user
        $entityManager->remove($targetUser);
        $entityManager->flush();

        $this->addFlash('success', 'User account has been deleted successfully.');
        return $this->redirectToRoute('admin_users_list');
    }

    private function deleteAllUserImages(User $user): void
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $profile = $user->getProfile();

        // Delete profile picture
        $profilePicturePath = $profile->getProfilePicturePath();
        if ($profilePicturePath && $profilePicturePath !== 'images/img_default_user.webp') {
            $pictureFile = $projectDir . '/public/' . $profilePicturePath;
            if (file_exists($pictureFile)) {
                unlink($pictureFile);
            }
        }

        // Delete all post images
        foreach ($user->getPosts() as $post) {
            if ($post->getImagePath()) {
                $imagePath = $projectDir . '/public/uploads/posts/' . $post->getImagePath();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }

        // Delete all project images
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getLabel() === 'Projects') {
                foreach ($section->getProjects() as $project) {
                    $imageFields = ['imagePath', 'imagePath2', 'imagePath3'];
                    foreach ($imageFields as $field) {
                        $getter = 'get' . ucfirst($field);
                        if (method_exists($project, $getter)) {
                            $imagePath = $project->$getter();
                            if ($imagePath) {
                                $fullPath = $projectDir . '/public/uploads/projects/' . $imagePath;
                                if (file_exists($fullPath)) {
                                    unlink($fullPath);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
