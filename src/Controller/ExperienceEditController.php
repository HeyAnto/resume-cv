<?php

namespace App\Controller;

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

    #[Route('/{username}/experience', name: 'app_experience_list')]
    public function experienceList(string $username, EntityManagerInterface $entityManager): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        // Get existing experiences
        $experiences = [];
        $workExperienceSection = null;
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getLabel() === 'Work Experience') {
                $experiences = $section->getExperiences()->toArray();
                $workExperienceSection = $section;
                break;
            }
        }

        return $this->render('profile-edit/experience-list.html.twig', [
            'username' => $username,
            'experiences' => $experiences,
            'workExperienceSection' => $workExperienceSection,
        ]);
    }

    #[Route('/{username}/experience/new', name: 'app_experience_new')]
    public function experienceNew(Request $request, EntityManagerInterface $entityManager, string $username): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        // Create new experience
        $experience = new Experience();

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

        // Save empty experience
        $experience->setResumeSection($workExperienceSection);
        $experience->setCompanyName('');
        $experience->setJob('');
        $experience->setLocation('');
        $experience->setStartDate(new \DateTimeImmutable());
        $experience->setCreatedAt(new \DateTimeImmutable());
        $experience->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->persist($experience);
        $entityManager->flush();

        // Redirect to edit
        return $this->redirectToRoute('app_experience_edit', [
            'username' => $username,
            'id' => $experience->getId()
        ]);
    }

    #[Route('/{username}/experience/{id}', name: 'app_experience_edit', requirements: ['id' => '\d+'])]
    public function experienceEdit(Request $request, EntityManagerInterface $entityManager, string $username, int $id): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        // Get experience
        $experience = $entityManager->getRepository(Experience::class)->find($id);

        if (!$experience || $experience->getResumeSection()->getProfile() !== $profile) {
            throw $this->createNotFoundException('Experience not found');
        }

        $form = $this->createForm(ExperienceFormType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $experience->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Experience updated successfully!');
            return $this->redirectToRoute('app_experience_list', ['username' => $username]);
        }

        return $this->render('profile-edit/experience-edit.html.twig', [
            'form' => $form,
            'username' => $username,
            'experience' => $experience,
        ]);
    }

    #[Route('/{username}/experience/{id}/delete', name: 'app_experience_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function experienceDelete(EntityManagerInterface $entityManager, string $username, int $id): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        $experience = $entityManager->getRepository(Experience::class)->find($id);

        if (!$experience || $experience->getResumeSection()->getProfile() !== $profile) {
            throw $this->createNotFoundException('Experience not found');
        }

        $resumeSection = $experience->getResumeSection();
        $entityManager->remove($experience);

        // Check remaining experiences
        $remainingExperiences = $resumeSection->getExperiences()->count() - 1; // -1 for deletion

        if ($remainingExperiences === 0) {
            $resumeSection->setIsVisible(false);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Experience deleted successfully!');
        return $this->redirectToRoute('app_experience_list', ['username' => $username]);
    }

    #[Route('/{username}/experience/toggle-visibility', name: 'app_experience_toggle_visibility', methods: ['POST'])]
    public function toggleVisibility(EntityManagerInterface $entityManager, string $username): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        // Find Work Experience section
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getLabel() === 'Work Experience') {
                $section->setIsVisible(!$section->isVisible());
                $entityManager->flush();

                $status = $section->isVisible() ? 'visible' : 'hidden';
                $this->addFlash('success', "Work Experience section is now {$status}!");
                break;
            }
        }

        return $this->redirectToRoute('app_experience_list', ['username' => $username]);
    }
}
