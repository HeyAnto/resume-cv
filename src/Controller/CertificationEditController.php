<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Certification;
use App\Entity\ResumeSection;
use App\Entity\User;
use App\Form\CertificationFormType;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/profile')]
final class CertificationEditController extends AbstractController
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

  #[Route('/{username}/certification', name: 'app_certification_list')]
  public function certificationList(string $username, ProfileRepository $profileRepository): Response
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

    // Get existing certifications
    $certifications = [];
    $certificationSection = null;
    foreach ($profile->getResumeSections() as $section) {
      if ($section->getLabel() === 'Certifications') {
        $certifications = $section->getCertifications()->toArray();
        // Sort by issuedDate desc
        usort($certifications, function ($a, $b) {
          return $b->getIssuedDate() <=> $a->getIssuedDate();
        });
        $certificationSection = $section;
        break;
      }
    }

    return $this->render('profile-edit/certification/certification-list.html.twig', [
      'username' => $username,
      'profile' => $profile,
      'certifications' => $certifications,
      'certificationSection' => $certificationSection,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/certification/new', name: 'app_certification_new')]
  public function certificationNew(Request $request, EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username): Response
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

    $certification = new Certification();

    $form = $this->createForm(CertificationFormType::class, $certification);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Find Certifications section
      $certificationSection = null;
      foreach ($profile->getResumeSections() as $section) {
        if ($section->getLabel() === 'Certifications') {
          $certificationSection = $section;
          break;
        }
      }

      // Create section if it doesn't exist
      if (!$certificationSection) {
        $certificationSection = new ResumeSection();
        $certificationSection->setLabel('Certifications');
        $certificationSection->setProfile($profile);
        $certificationSection->setIsVisible(true);
        $certificationSection->setCreatedAt(new \DateTimeImmutable());
        $certificationSection->setUpdatedAt(new \DateTimeImmutable());
        // Set order (after existing sections)
        $maxOrder = 0;
        foreach ($profile->getResumeSections() as $section) {
          if ($section->getOrderIndex() > $maxOrder) {
            $maxOrder = $section->getOrderIndex();
          }
        }
        $certificationSection->setOrderIndex($maxOrder + 1);
        $entityManager->persist($certificationSection);
      }

      $certification->setResumeSection($certificationSection);
      $certification->setCreatedAt(new \DateTimeImmutable());
      $certification->setUpdatedAt(new \DateTimeImmutable());

      $entityManager->persist($certification);
      $profile->setUpdatedAt(new \DateTimeImmutable());
      $entityManager->flush();

      $this->addFlash('success', 'Certification added successfully!');
      return $this->redirectToRoute('app_certification_list', ['username' => $username]);
    }

    return $this->render('profile-edit/certification/certification-edit.html.twig', [
      'form' => $form,
      'username' => $username,
      'profile' => $profile,
      'certification' => null,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/certification/{id}', name: 'app_certification_edit', requirements: ['id' => '\d+'])]
  public function certificationEdit(Request $request, EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username, int $id): Response
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

    // Get certification
    $certification = $entityManager->getRepository(Certification::class)->find($id);

    if (!$certification || $certification->getResumeSection()->getProfile() !== $profile) {
      throw $this->createNotFoundException('Certification not found');
    }

    $form = $this->createForm(CertificationFormType::class, $certification);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $certification->setUpdatedAt(new \DateTimeImmutable());
      $profile->setUpdatedAt(new \DateTimeImmutable());
      $entityManager->flush();

      $this->addFlash('success', 'Certification updated successfully!');
      return $this->redirectToRoute('app_certification_list', ['username' => $username]);
    }

    return $this->render('profile-edit/certification/certification-edit.html.twig', [
      'form' => $form,
      'username' => $username,
      'profile' => $profile,
      'certification' => $certification,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/certification/{id}/delete', name: 'app_certification_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
  public function certificationDelete(EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username, int $id): Response
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

    $certification = $entityManager->getRepository(Certification::class)->find($id);

    if (!$certification || $certification->getResumeSection()->getProfile() !== $profile) {
      throw $this->createNotFoundException('Certification not found');
    }

    $resumeSection = $certification->getResumeSection();
    $entityManager->remove($certification);

    // Check remaining certifications
    $remainingCertifications = $resumeSection->getCertifications()->count() - 1;

    if ($remainingCertifications === 0) {
      $entityManager->remove($resumeSection);
    }

    $profile->setUpdatedAt(new \DateTimeImmutable());
    $entityManager->flush();

    $this->addFlash('success', 'Certification deleted successfully!');
    return $this->redirectToRoute('app_certification_list', ['username' => $username]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/certification/toggle-visibility', name: 'app_certification_toggle_visibility', methods: ['POST'])]
  public function toggleVisibility(EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username): Response
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

    // Find certification section
    $certificationSection = null;
    foreach ($profile->getResumeSections() as $section) {
      if ($section->getLabel() === 'Certifications') {
        $certificationSection = $section;
        break;
      }
    }

    if ($certificationSection) {
      $certificationSection->setIsVisible(!$certificationSection->isVisible());
      $profile->setUpdatedAt(new \DateTimeImmutable());
      $entityManager->flush();

      $message = $certificationSection->isVisible() ? 'Certifications section is now visible' : 'Certifications section is now hidden';
      $this->addFlash('success', $message);
    }

    return $this->redirectToRoute('app_certification_list', ['username' => $username]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/certification/change-order', name: 'app_certification_change_order', methods: ['POST'])]
  public function changeOrder(Request $request, EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username): Response
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

    $direction = $request->request->get('direction');

    // Find certification section
    $certificationSection = null;
    foreach ($profile->getResumeSections() as $section) {
      if ($section->getLabel() === 'Certifications') {
        $certificationSection = $section;
        break;
      }
    }

    if (!$certificationSection) {
      $this->addFlash('error', 'Certifications section not found');
      return $this->redirectToRoute('app_certification_list', ['username' => $username]);
    }

    $currentOrder = $certificationSection->getOrderIndex();

    // Get all sections sorted by order
    $sections = $profile->getResumeSections()->toArray();
    usort($sections, function ($a, $b) {
      return $a->getOrderIndex() <=> $b->getOrderIndex();
    });

    $currentIndex = array_search($certificationSection, $sections);

    if ($direction === 'up' && $currentIndex > 0) {
      $previousSection = $sections[$currentIndex - 1];
      $tempOrder = $certificationSection->getOrderIndex();
      $certificationSection->setOrderIndex($previousSection->getOrderIndex());
      $previousSection->setOrderIndex($tempOrder);
    } elseif ($direction === 'down' && $currentIndex < count($sections) - 1) {
      $nextSection = $sections[$currentIndex + 1];
      $tempOrder = $certificationSection->getOrderIndex();
      $certificationSection->setOrderIndex($nextSection->getOrderIndex());
      $nextSection->setOrderIndex($tempOrder);
    }

    $profile->setUpdatedAt(new \DateTimeImmutable());
    $entityManager->flush();

    return $this->redirectToRoute('app_certification_list', ['username' => $username]);
  }
}
