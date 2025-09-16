<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Profile;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profile')]
final class ProfileController extends AbstractController
{
    use UserRedirectionTrait;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}', name: 'app_profile')]
    public function profile(string $username, EntityManagerInterface $entityManager): Response
    {
        $redirectResponse = $this->checkUserAccess();
        if ($redirectResponse) {
            return $redirectResponse;
        }

        $profile = $entityManager->getRepository(Profile::class)->findOneBy(['username' => $username]);

        if (!$profile) {
            return $this->redirectToRoute('app_not_found');
        }

        // Hide unverified users
        if (!$profile->getUser()->isVerified() && !$this->isGranted('ROLE_ADMIN')) {
            /** @var User|null $currentUser */
            $currentUser = $this->getUser();
            if (!$currentUser || $currentUser->getProfile()->getUsername() !== $username) {
                return $this->redirectToRoute('app_not_found');
            }
        }

        // Sort experiences by date
        foreach ($profile->getResumeSections() as $section) {
            $experiences = $section->getExperiences()->toArray();
            usort($experiences, function ($a, $b) {
                if ($a->getEndDate() === null && $b->getEndDate() !== null) {
                    return -1;
                }
                if ($a->getEndDate() !== null && $b->getEndDate() === null) {
                    return 1;
                }

                return $b->getStartDate() <=> $a->getStartDate();
            });
            $section->getExperiences()->clear();
            foreach ($experiences as $experience) {
                $section->getExperiences()->add($experience);
            }
        }

        // Sort resume sections by orderIndex
        $resumeSections = $profile->getResumeSections()->toArray();
        usort($resumeSections, function ($a, $b) {
            return $a->getOrderIndex() <=> $b->getOrderIndex();
        });

        return $this->render('profile/profile.html.twig', [
            'username' => $username,
            'profile' => $profile,
            'resumeSections' => $resumeSections
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/posts', name: 'app_profile_posts')]
    public function profilePosts(string $username, EntityManagerInterface $entityManager): Response
    {
        $redirectResponse = $this->checkUserAccess();
        if ($redirectResponse) {
            return $redirectResponse;
        }

        $profile = $entityManager->getRepository(Profile::class)->findOneBy(['username' => $username]);

        if (!$profile) {
            return $this->redirectToRoute('app_not_found');
        }

        // Hide unverified users' profiles unless you're admin or the owner
        if (!$profile->getUser()->isVerified() && !$this->isGranted('ROLE_ADMIN')) {
            /** @var User|null $currentUser */
            $currentUser = $this->getUser();
            if (!$currentUser || $currentUser->getProfile()->getUsername() !== $username) {
                return $this->redirectToRoute('app_not_found');
            }
        }

        // User posts visible sorted -> most recent
        $posts = $entityManager->getRepository(\App\Entity\Post::class)->findBy(
            ['user' => $profile->getUser(), 'isVisible' => true],
            ['createdAt' => 'DESC']
        );

        return $this->render('profile/profile-posts.html.twig', [
            'username' => $username,
            'profile' => $profile,
            'posts' => $posts
        ]);
    }
}
