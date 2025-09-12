<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\User;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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
    public function profileEdit(string $username, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
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

        $profile = $user->getProfile();
        $form = $this->createForm(ProfileFormType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $profilePictureFile */
            $profilePictureFile = $form->get('profilePicture')->getData();

            if ($profilePictureFile) {
                // Validate MIME type
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($profilePictureFile->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Please upload a valid image file (JPEG, PNG, WebP)');
                    return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
                }

                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();

                try {
                    $profilePictureFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/profile-pictures',
                        $newFilename
                    );

                    // Supprimer l'ancienne image si ce n'est pas l'image par défaut
                    $oldPath = $profile->getProfilePicturePath();
                    if ($oldPath && $oldPath !== 'images/img_default_user.webp') {
                        $oldFile = $this->getParameter('kernel.project_dir') . '/public/' . $oldPath;
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }

                    $profile->setProfilePicturePath('uploads/profile-pictures/' . $newFilename);
                } catch (FileException $e) {
                    // Gérer l'erreur d'upload
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_profile', ['username' => $profile->getUsername()]);
        }

        return $this->render('profile/profile-edit.html.twig', [
            'username' => $username,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{username}/remove-picture', name: 'app_profile_remove_picture', methods: ['POST'])]
    public function removePicture(string $username, EntityManagerInterface $entityManager): Response
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

        $profile = $user->getProfile();
        $oldPath = $profile->getProfilePicturePath();

        // Supprimer l'ancienne image si ce n'est pas l'image par défaut
        if ($oldPath && $oldPath !== 'images/img_default_user.webp') {
            $oldFile = $this->getParameter('kernel.project_dir') . '/public/' . $oldPath;
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // Remettre l'image par défaut
        $profile->setProfilePicturePath('images/img_default_user.webp');
        $entityManager->flush();

        return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
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
