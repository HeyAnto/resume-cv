<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Volunteering;
use App\Entity\ResumeSection;
use App\Entity\User;
use App\Form\VolunteeringFormType;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/profile')]
final class VolunteeringEditController extends AbstractController
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

  #[Route('/{username}/volunteering', name: 'app_volunteering_list')]
  public function volunteeringList(string $username, ProfileRepository $profileRepository): Response
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

    // Get existing volunteering
    $volunteerings = [];
    $volunteeringSection = null;
    foreach ($profile->getResumeSections() as $section) {
      if ($section->getLabel() === 'Volunteering') {
        $volunteerings = $section->getVolunteerings()->toArray();
        // Sort by startDate desc
        usort($volunteerings, function ($a, $b) {
          return $b->getStartDate() <=> $a->getStartDate();
        });
        $volunteeringSection = $section;
        break;
      }
    }

    return $this->render('profile-edit/volunteering/volunteering-list.html.twig', [
      'username' => $username,
      'profile' => $profile,
      'volunteerings' => $volunteerings,
      'volunteeringSection' => $volunteeringSection,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/volunteering/new', name: 'app_volunteering_new')]
  public function volunteeringNew(Request $request, EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username): Response
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

    $volunteering = new Volunteering();

    $form = $this->createForm(VolunteeringFormType::class, $volunteering);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Find Volunteering section
      $volunteeringSection = null;
      foreach ($profile->getResumeSections() as $section) {
        if ($section->getLabel() === 'Volunteering') {
          $volunteeringSection = $section;
          break;
        }
      }

      // Create section if it doesn't exist
      if (!$volunteeringSection) {
        $volunteeringSection = new ResumeSection();
        $volunteeringSection->setLabel('Volunteering');
        $volunteeringSection->setProfile($profile);
        $volunteeringSection->setIsVisible(true);
        $volunteeringSection->setCreatedAt(new \DateTimeImmutable());
        $volunteeringSection->setUpdatedAt(new \DateTimeImmutable());
        // Set order (after existing sections)
        $maxOrder = 0;
        foreach ($profile->getResumeSections() as $section) {
          if ($section->getOrderIndex() > $maxOrder) {
            $maxOrder = $section->getOrderIndex();
          }
        }
        $volunteeringSection->setOrderIndex($maxOrder + 1);
        $entityManager->persist($volunteeringSection);
      }

      $volunteering->setResumeSection($volunteeringSection);
      $volunteering->setCreatedAt(new \DateTimeImmutable());
      $volunteering->setUpdatedAt(new \DateTimeImmutable());

      $entityManager->persist($volunteering);
      $profile->setUpdatedAt(new \DateTimeImmutable());
      $entityManager->flush();

      $this->addFlash('success', 'Volunteering added successfully!');
      return $this->redirectToRoute('app_volunteering_list', ['username' => $username]);
    }

    return $this->render('profile-edit/volunteering/volunteering-edit.html.twig', [
      'form' => $form,
      'username' => $username,
      'profile' => $profile,
      'volunteering' => null,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/volunteering/{id}', name: 'app_volunteering_edit', requirements: ['id' => '\d+'])]
  public function volunteeringEdit(Request $request, EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username, int $id): Response
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

    // Get volunteering
    $volunteering = $entityManager->getRepository(Volunteering::class)->find($id);

    if (!$volunteering || $volunteering->getResumeSection()->getProfile() !== $profile) {
      throw $this->createNotFoundException('Volunteering not found');
    }

    $form = $this->createForm(VolunteeringFormType::class, $volunteering);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $volunteering->setUpdatedAt(new \DateTimeImmutable());
      $profile->setUpdatedAt(new \DateTimeImmutable());
      $entityManager->flush();

      $this->addFlash('success', 'Volunteering updated successfully!');
      return $this->redirectToRoute('app_volunteering_list', ['username' => $username]);
    }

    return $this->render('profile-edit/volunteering/volunteering-edit.html.twig', [
      'form' => $form,
      'username' => $username,
      'profile' => $profile,
      'volunteering' => $volunteering,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/volunteering/{id}/delete', name: 'app_volunteering_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
  public function volunteeringDelete(EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username, int $id): Response
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

    $volunteering = $entityManager->getRepository(Volunteering::class)->find($id);

    if (!$volunteering || $volunteering->getResumeSection()->getProfile() !== $profile) {
      throw $this->createNotFoundException('Volunteering not found');
    }

    $resumeSection = $volunteering->getResumeSection();
    $entityManager->remove($volunteering);

    // Check remaining volunteerings
    $remainingVolunteerings = $resumeSection->getVolunteerings()->count() - 1;

    if ($remainingVolunteerings === 0) {
      $entityManager->remove($resumeSection);
    }

    $profile->setUpdatedAt(new \DateTimeImmutable());
    $entityManager->flush();

    $this->addFlash('success', 'Volunteering deleted successfully!');
    return $this->redirectToRoute('app_volunteering_list', ['username' => $username]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/volunteering/toggle-visibility', name: 'app_volunteering_toggle_visibility', methods: ['POST'])]
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

    // Find volunteering section
    $volunteeringSection = null;
    foreach ($profile->getResumeSections() as $section) {
      if ($section->getLabel() === 'Volunteering') {
        $volunteeringSection = $section;
        break;
      }
    }

    if ($volunteeringSection) {
      $volunteeringSection->setIsVisible(!$volunteeringSection->isVisible());
      $profile->setUpdatedAt(new \DateTimeImmutable());
      $entityManager->flush();

      $message = $volunteeringSection->isVisible() ? 'Volunteering section is now visible' : 'Volunteering section is now hidden';
      $this->addFlash('success', $message);
    }

    return $this->redirectToRoute('app_volunteering_list', ['username' => $username]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/volunteering/change-order', name: 'app_volunteering_change_order', methods: ['POST'])]
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

    // Find volunteering section
    $volunteeringSection = null;
    foreach ($profile->getResumeSections() as $section) {
      if ($section->getLabel() === 'Volunteering') {
        $volunteeringSection = $section;
        break;
      }
    }

    if (!$volunteeringSection) {
      $this->addFlash('error', 'Volunteering section not found');
      return $this->redirectToRoute('app_volunteering_list', ['username' => $username]);
    }

    $currentOrder = $volunteeringSection->getOrderIndex();

    // Get all sections sorted by order
    $sections = $profile->getResumeSections()->toArray();
    usort($sections, function ($a, $b) {
      return $a->getOrderIndex() <=> $b->getOrderIndex();
    });

    $currentIndex = array_search($volunteeringSection, $sections);

    if ($direction === 'up' && $currentIndex > 0) {
      $previousSection = $sections[$currentIndex - 1];
      $tempOrder = $volunteeringSection->getOrderIndex();
      $volunteeringSection->setOrderIndex($previousSection->getOrderIndex());
      $previousSection->setOrderIndex($tempOrder);
    } elseif ($direction === 'down' && $currentIndex < count($sections) - 1) {
      $nextSection = $sections[$currentIndex + 1];
      $tempOrder = $volunteeringSection->getOrderIndex();
      $volunteeringSection->setOrderIndex($nextSection->getOrderIndex());
      $nextSection->setOrderIndex($tempOrder);
    }

    $profile->setUpdatedAt(new \DateTimeImmutable());
    $entityManager->flush();

    return $this->redirectToRoute('app_volunteering_list', ['username' => $username]);
  }
}
