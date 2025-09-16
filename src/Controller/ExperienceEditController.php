<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Experience;
use App\Entity\ResumeSection;
use App\Entity\User;
use App\Form\ExperienceFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/profile')]
final class ExperienceEditController extends AbstractController
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

    #[Route('/{username}/experience', name: 'app_experience_list')]
    public function experienceList(string $username, EntityManagerInterface $entityManager): Response
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

        // Get existing experiences
        $experiences = [];
        $workExperienceSection = null;
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getLabel() === 'Work Experience') {
                $experiences = $section->getExperiences()->toArray();
                // Sort by startDate desc
                usort($experiences, function ($a, $b) {
                    return $b->getStartDate() <=> $a->getStartDate();
                });
                $workExperienceSection = $section;
                break;
            }
        }

        return $this->render('profile-edit/experience/experience-list.html.twig', [
            'username' => $username,
            'profile' => $profile,
            'experiences' => $experiences,
            'workExperienceSection' => $workExperienceSection,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/experience/new', name: 'app_experience_new')]
    public function experienceNew(Request $request, EntityManagerInterface $entityManager, string $username): Response
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

        $experience = new Experience();

        $form = $this->createForm(ExperienceFormType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Find Work Experience section
            $workExperienceSection = null;
            foreach ($profile->getResumeSections() as $section) {
                if ($section->getLabel() === 'Work Experience') {
                    $workExperienceSection = $section;
                    break;
                }
            }

            if (!$workExperienceSection) {
                $workExperienceSection = new ResumeSection();
                $workExperienceSection->setLabel('Work Experience');
                $workExperienceSection->setOrderIndex(1);
                $workExperienceSection->setIsVisible(true);
                $workExperienceSection->setCreatedAt(new \DateTimeImmutable());
                $workExperienceSection->setUpdatedAt(new \DateTimeImmutable());
                $workExperienceSection->setProfile($profile);
                $entityManager->persist($workExperienceSection);
            }

            $experience->setResumeSection($workExperienceSection);
            $experience->setCreatedAt(new \DateTimeImmutable());
            $experience->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($experience);
            $profile->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Experience created successfully!');
            return $this->redirectToRoute('app_experience_list', ['username' => $username]);
        }

        return $this->render('profile-edit/experience/experience-edit.html.twig', [
            'form' => $form,
            'username' => $username,
            'profile' => $profile,
            'experience' => null,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/experience/{id}', name: 'app_experience_edit', requirements: ['id' => '\d+'])]
    public function experienceEdit(Request $request, EntityManagerInterface $entityManager, string $username, int $id): Response
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

        // Get experience
        $experience = $entityManager->getRepository(Experience::class)->find($id);

        if (!$experience || $experience->getResumeSection()->getProfile() !== $profile) {
            throw $this->createNotFoundException('Experience not found');
        }

        $form = $this->createForm(ExperienceFormType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $experience->setUpdatedAt(new \DateTimeImmutable());
            $profile->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Experience updated successfully!');
            return $this->redirectToRoute('app_experience_list', ['username' => $username]);
        }

        return $this->render('profile-edit/experience/experience-edit.html.twig', [
            'form' => $form,
            'username' => $username,
            'profile' => $profile,
            'experience' => $experience,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/experience/{id}/delete', name: 'app_experience_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function experienceDelete(EntityManagerInterface $entityManager, string $username, int $id): Response
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

        $experience = $entityManager->getRepository(Experience::class)->find($id);

        if (!$experience || $experience->getResumeSection()->getProfile() !== $profile) {
            throw $this->createNotFoundException('Experience not found');
        }

        $resumeSection = $experience->getResumeSection();
        $entityManager->remove($experience);

        // Check remaining experiences
        $remainingExperiences = $resumeSection->getExperiences()->count() - 1;

        if ($remainingExperiences === 0) {
            $resumeSection->setIsVisible(false);
        }

        $profile->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Experience deleted successfully!');
        return $this->redirectToRoute('app_experience_list', ['username' => $username]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/experience/toggle-visibility', name: 'app_experience_toggle_visibility', methods: ['POST'])]
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

        // Find Work Experience section
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getLabel() === 'Work Experience') {
                $section->setIsVisible(!$section->isVisible());
                $profile->setUpdatedAt(new \DateTimeImmutable());
                $entityManager->flush();

                $status = $section->isVisible() ? 'visible' : 'hidden';
                $this->addFlash('success', "Work Experience section is now {$status}!");
                break;
            }
        }

        return $this->redirectToRoute('app_experience_list', ['username' => $username]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/experience/change-order', name: 'app_experience_change_order', methods: ['POST'])]
    public function changeOrder(Request $request, EntityManagerInterface $entityManager, string $username): Response
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

        $direction = $request->request->get('direction');

        // Find Work Experience section
        $workExperienceSection = null;
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getLabel() === 'Work Experience') {
                $workExperienceSection = $section;
                break;
            }
        }

        if (!$workExperienceSection) {
            $this->addFlash('error', 'Work Experience section not found');
            return $this->redirectToRoute('app_experience_list', ['username' => $username]);
        }

        $currentOrder = $workExperienceSection->getOrderIndex();
        $newOrder = $direction === 'up' ? $currentOrder - 1 : $currentOrder + 1;

        // Find section with the target order index
        $targetSection = null;
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getOrderIndex() === $newOrder) {
                $targetSection = $section;
                break;
            }
        }

        if ($targetSection) {
            // Swap order indexes
            $targetSection->setOrderIndex($currentOrder);
            $workExperienceSection->setOrderIndex($newOrder);

            $profile->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Section order updated successfully!');
        } else {
            $this->addFlash('error', 'Cannot move section in that direction');
        }

        return $this->redirectToRoute('app_experience_list', ['username' => $username]);
    }
}
