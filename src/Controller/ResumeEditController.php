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
final class ResumeEditController extends AbstractController
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

    #[Route('/{username}/experience', name: 'app_experience_edit')]
    public function experienceEdit(Request $request, EntityManagerInterface $entityManager, string $username): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        // Créer une nouvelle expérience
        $experience = new Experience();
        $form = $this->createForm(ExperienceFormType::class, $experience);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Chercher ou créer la section "Work Experience"
            $workExperienceSection = null;
            foreach ($profile->getResumeSections() as $section) {
                if ($section->getLabel() === 'Work Experience') {
                    $workExperienceSection = $section;
                    break;
                }
            }

            // Si la section n'existe pas, la créer
            if (!$workExperienceSection) {
                $workExperienceSection = new ResumeSection();
                $workExperienceSection->setLabel('Work Experience');
                $workExperienceSection->setOrderIndex(1); // Première section par défaut
                $workExperienceSection->setIsVisible(true);
                $workExperienceSection->setCreatedAt(new \DateTimeImmutable());
                $workExperienceSection->setUpdatedAt(new \DateTimeImmutable());
                $workExperienceSection->setProfile($profile);

                $entityManager->persist($workExperienceSection);
            }

            // Associer l'expérience à la section
            $experience->setResumeSection($workExperienceSection);
            $experience->setCreatedAt(new \DateTimeImmutable());
            $experience->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($experience);
            $entityManager->flush();

            $this->addFlash('success', 'Experience added successfully!');
            return $this->redirectToRoute('app_experience_edit', ['username' => $username]);
        }

        return $this->render('profile-edit/experience-edit.html.twig', [
            'form' => $form,
            'username' => $username,
        ]);
    }
}
