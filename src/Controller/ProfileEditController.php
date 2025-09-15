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
final class ProfileEditController extends AbstractController
{
  use UserRedirectionTrait;

  private function checkUsernameAccess(string $username): ?Response
  {
    /** @var User $user */
    $user = $this->getUser();
    $profile = $user->getProfile();

    // Redirect to public profile
    if ($profile->getUsername() !== $username) {
      return $this->redirectToRoute('app_profile', ['username' => $username]);
    }

    return null;
  }

  #[Route('/{username}/general', name: 'app_profile_edit')]
  public function profileEdit(string $username, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
  {
    $redirectResponse = $this->checkUserAccess();
    if ($redirectResponse) {
      return $redirectResponse;
    }

    $usernameCheck = $this->checkUsernameAccess($username);
    if ($usernameCheck) {
      return $usernameCheck;
    }

    /** @var User $user */
    $user = $this->getUser();
    $profile = $user->getProfile();

    $form = $this->createForm(ProfileFormType::class, $profile);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      /** @var UploadedFile|null $profilePictureFile */
      $profilePictureFile = $form->get('profilePicture')->getData();

      // Save form data first
      $entityManager->flush();

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

          // Delete old image
          $oldPath = $profile->getProfilePicturePath();
          if ($oldPath && $oldPath !== 'images/img_default_user.webp') {
            $oldFile = $this->getParameter('kernel.project_dir') . '/public/' . $oldPath;
            if (file_exists($oldFile)) {
              unlink($oldFile);
            }
          }

          $profile->setProfilePicturePath('uploads/profile-pictures/' . $newFilename);
          $entityManager->flush();
          $this->addFlash('success', 'Profile picture successfully updated');
        } catch (FileException $e) {
          $this->addFlash('error', 'Error uploading image');
        }

        return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
      }

      $this->addFlash('success', 'Profile successfully updated');
      return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
    }

    return $this->render('profile-edit/profile-edit.html.twig', [
      'username' => $username,
      'form' => $form->createView(),
    ]);
  }

  #[Route('/{username}/general/remove-picture', name: 'app_profile_remove_picture', methods: ['GET', 'POST'])]
  public function removePicture(string $username, EntityManagerInterface $entityManager, Request $request): Response
  {
    $redirectResponse = $this->checkUserAccess();
    if ($redirectResponse) {
      return $redirectResponse;
    }

    $usernameCheck = $this->checkUsernameAccess($username);
    if ($usernameCheck) {
      return $usernameCheck;
    }

    // If GET -> redirect to profile edit
    if ($request->getMethod() === 'GET') {
      return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
    }

    /** @var User $user */
    $user = $this->getUser();
    $profile = $user->getProfile();
    $oldPath = $profile->getProfilePicturePath();

    // Delete old image
    if ($oldPath && $oldPath !== 'images/img_default_user.webp') {
      $oldFile = $this->getParameter('kernel.project_dir') . '/public/' . $oldPath;
      if (file_exists($oldFile)) {
        unlink($oldFile);
      }
    }

    // Reset default image
    $profile->setProfilePicturePath('images/img_default_user.webp');
    $entityManager->flush();

    $this->addFlash('success', 'Profile picture successfully deleted');

    return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
  }
}
