<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Education;
use App\Entity\ResumeSection;
use App\Entity\User;
use App\Form\EducationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/profile')]
final class EducationEditController extends AbstractController
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

    // Redirect to profile
    return $this->redirectToRoute('app_profile', ['username' => $username]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/education', name: 'app_education_list')]
  public function educationList(string $username, EntityManagerInterface $entityManager): Response
  {
    $usernameCheck = $this->checkUsernameAccess($username);
    if ($usernameCheck) {
      return $usernameCheck;
    }

    // Get user and profile by username
    $targetUser = $entityManager->getRepository(User::class)
      ->createQueryBuilder('u')
      ->join('u.profile', 'p')
      ->where('p.username = :username')
      ->setParameter('username', $username)
      ->getQuery()
      ->getOneOrNullResult();

    if (!$targetUser) {
      throw $this->createNotFoundException('User not found');
    }
    $profile = $targetUser->getProfile();

    // Get existing educations
    $educations = [];
    $educationSection = null;
    foreach ($profile->getResumeSections() as $section) {
      if ($section->getLabel() === 'Education') {
        $educations = $section->getEducations()->toArray();
        // Sort by startDate desc
        usort($educations, function ($a, $b) {
          return $b->getStartDate() <=> $a->getStartDate();
        });
        $educationSection = $section;
        break;
      }
    }

    return $this->render('profile-edit/education/education-list.html.twig', [
      'username' => $username,
      'educations' => $educations,
      'educationSection' => $educationSection,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/education/new', name: 'app_education_new')]
  public function educationNew(Request $request, EntityManagerInterface $entityManager, string $username): Response
  {
    $usernameCheck = $this->checkUsernameAccess($username);
    if ($usernameCheck) {
      return $usernameCheck;
    }

    // Get user and profile by username
    $targetUser = $entityManager->getRepository(User::class)
      ->createQueryBuilder('u')
      ->join('u.profile', 'p')
      ->where('p.username = :username')
      ->setParameter('username', $username)
      ->getQuery()
      ->getOneOrNullResult();

    if (!$targetUser) {
      throw $this->createNotFoundException('User not found');
    }
    $profile = $targetUser->getProfile();

    $education = new Education();

    $form = $this->createForm(EducationFormType::class, $education);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Find Education section
      $educationSection = null;
      foreach ($profile->getResumeSections() as $section) {
        if ($section->getLabel() === 'Education') {
          $educationSection = $section;
          break;
        }
      }

      if (!$educationSection) {
        $educationSection = new ResumeSection();
        $educationSection->setLabel('Education');
        $educationSection->setOrderIndex(2);
        $educationSection->setIsVisible(true);
        $educationSection->setCreatedAt(new \DateTimeImmutable());
        $educationSection->setUpdatedAt(new \DateTimeImmutable());
        $educationSection->setProfile($profile);
        $entityManager->persist($educationSection);
      }

      $education->setResumeSection($educationSection);
      $education->setCreatedAt(new \DateTimeImmutable());
      $education->setUpdatedAt(new \DateTimeImmutable());

      $entityManager->persist($education);
      $entityManager->flush();

      $this->addFlash('success', 'Education created successfully!');
      return $this->redirectToRoute('app_education_list', ['username' => $username]);
    }

    return $this->render('profile-edit/education/education-edit.html.twig', [
      'form' => $form,
      'username' => $username,
      'education' => null,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/education/{id}', name: 'app_education_edit', requirements: ['id' => '\d+'])]
  public function educationEdit(Request $request, EntityManagerInterface $entityManager, string $username, int $id): Response
  {
    $usernameCheck = $this->checkUsernameAccess($username);
    if ($usernameCheck) {
      return $usernameCheck;
    }

    // Get user and profile by username
    $targetUser = $entityManager->getRepository(User::class)
      ->createQueryBuilder('u')
      ->join('u.profile', 'p')
      ->where('p.username = :username')
      ->setParameter('username', $username)
      ->getQuery()
      ->getOneOrNullResult();

    if (!$targetUser) {
      throw $this->createNotFoundException('User not found');
    }
    $profile = $targetUser->getProfile();

    // Get education
    $education = $entityManager->getRepository(Education::class)->find($id);

    if (!$education || $education->getResumeSection()->getProfile() !== $profile) {
      throw $this->createNotFoundException('Education not found');
    }

    $form = $this->createForm(EducationFormType::class, $education);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $education->setUpdatedAt(new \DateTimeImmutable());
      $entityManager->flush();

      $this->addFlash('success', 'Education updated successfully!');
      return $this->redirectToRoute('app_education_list', ['username' => $username]);
    }

    return $this->render('profile-edit/education/education-edit.html.twig', [
      'form' => $form,
      'username' => $username,
      'education' => $education,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/education/{id}/delete', name: 'app_education_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
  public function educationDelete(EntityManagerInterface $entityManager, string $username, int $id): Response
  {
    $usernameCheck = $this->checkUsernameAccess($username);
    if ($usernameCheck) {
      return $usernameCheck;
    }

    // Get user and profile by username
    $targetUser = $entityManager->getRepository(User::class)
      ->createQueryBuilder('u')
      ->join('u.profile', 'p')
      ->where('p.username = :username')
      ->setParameter('username', $username)
      ->getQuery()
      ->getOneOrNullResult();

    if (!$targetUser) {
      throw $this->createNotFoundException('User not found');
    }
    $profile = $targetUser->getProfile();

    $education = $entityManager->getRepository(Education::class)->find($id);

    if (!$education || $education->getResumeSection()->getProfile() !== $profile) {
      throw $this->createNotFoundException('Education not found');
    }

    $resumeSection = $education->getResumeSection();
    $entityManager->remove($education);

    // Check remaining educations
    $remainingEducations = $resumeSection->getEducations()->count() - 1;

    if ($remainingEducations === 0) {
      $resumeSection->setIsVisible(false);
    }

    $entityManager->flush();

    $this->addFlash('success', 'Education deleted successfully!');
    return $this->redirectToRoute('app_education_list', ['username' => $username]);
  }

  #[Route('/{username}/education/toggle-visibility', name: 'app_education_toggle_visibility', methods: ['POST'])]
  public function toggleVisibility(EntityManagerInterface $entityManager, string $username): Response
  {
    $usernameCheck = $this->checkUsernameAccess($username);
    if ($usernameCheck) {
      return $usernameCheck;
    }

    // Get user and profile by username
    $targetUser = $entityManager->getRepository(User::class)
      ->createQueryBuilder('u')
      ->join('u.profile', 'p')
      ->where('p.username = :username')
      ->setParameter('username', $username)
      ->getQuery()
      ->getOneOrNullResult();

    if (!$targetUser) {
      throw $this->createNotFoundException('User not found');
    }
    $profile = $targetUser->getProfile();

    // Find Education section
    foreach ($profile->getResumeSections() as $section) {
      if ($section->getLabel() === 'Education') {
        $section->setIsVisible(!$section->isVisible());
        $entityManager->flush();

        $status = $section->isVisible() ? 'visible' : 'hidden';
        $this->addFlash('success', "Education section is now {$status}!");
        break;
      }
    }

    return $this->redirectToRoute('app_education_list', ['username' => $username]);
  }
}
