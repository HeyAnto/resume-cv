<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\User;
use App\Form\ProfileFormType;
use App\Repository\ProfileRepository;
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

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  private function checkUsernameAccess(string $username): ?Response
  {
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

  #[Route('/{username}/general', name: 'app_profile_edit')]
  public function profileEdit(string $username, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProfileRepository $profileRepository): Response
  {
    $redirectResponse = $this->checkUserAccess();
    if ($redirectResponse) {
      return $redirectResponse;
    }

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

    // Upload profile picture
    /** @var UploadedFile|null $profilePictureFile */
    $profilePictureFile = $request->files->get('profilePicture');

    if ($profilePictureFile) {
      $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!in_array($profilePictureFile->getMimeType(), $allowedMimeTypes)) {
        $this->addFlash('error', 'Please upload a valid image file (JPEG, PNG, WebP)');
        return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
      }

      // Validate file size (2MB max)
      $maxSize = 2 * 1024 * 1024; // 2MB in bytes
      if ($profilePictureFile->getSize() > $maxSize) {
        $this->addFlash('error', 'Profile picture file is too large. Max size is 2MB.');
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
        $profile->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();
        $this->addFlash('success', 'Profile picture successfully updated');
      } catch (FileException $e) {
        $this->addFlash('error', 'Error uploading image');
      }

      return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
    }

    // Profile information
    $form = $this->createForm(ProfileFormType::class, $profile);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $profile->setUpdatedAt(new \DateTimeImmutable());
      $entityManager->flush();
      $this->addFlash('success', 'Profile successfully updated');
      return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
    }

    return $this->render('profile-edit/profile-edit.html.twig', [
      'username' => $username,
      'profile' => $profile,
      'form' => $form->createView(),
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/general/remove-picture', name: 'app_profile_remove_picture', methods: ['GET'])]
  public function removePicture(string $username, EntityManagerInterface $entityManager, ProfileRepository $profileRepository): Response
  {
    $redirectResponse = $this->checkUserAccess();
    if ($redirectResponse) {
      return $redirectResponse;
    }

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
    $profile->setUpdatedAt(new \DateTimeImmutable());
    $entityManager->flush();

    $this->addFlash('success', 'Profile picture successfully deleted');

    return $this->redirectToRoute('app_profile_edit', ['username' => $username]);
  }
}
