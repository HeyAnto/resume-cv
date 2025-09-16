<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Post;
use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class FollowingController extends AbstractController
{
    use UserRedirectionTrait;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/following', name: 'app_following')]
    public function index(): Response
    {
        $redirectResponse = $this->checkUserAccess();
        if ($redirectResponse) {
            return $redirectResponse;
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $currentProfile = $user->getProfile();
        
        // Récupérer les profils suivis par l'utilisateur connecté
        $followedProfiles = $currentProfile->getFollowing();

        // Récupérer les posts des profils suivis seulement
        $posts = [];
        if (!$followedProfiles->isEmpty()) {
            $posts = $this->entityManager->getRepository(Post::class)
                ->createQueryBuilder('p')
                ->join('p.user', 'u')
                ->join('u.profile', 'profile')
                ->where('profile IN (:followedProfiles)')
                ->andWhere('p.isVisible = :isVisible')
                ->setParameter('followedProfiles', $followedProfiles)
                ->setParameter('isVisible', true)
                ->orderBy('p.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Récupérer des suggestions de profils si l'utilisateur ne suit personne
        $suggestedProfiles = [];
        if ($followedProfiles->isEmpty()) {
            $suggestedProfiles = $this->entityManager->getRepository(Profile::class)
                ->createQueryBuilder('p')
                ->join('p.user', 'u')
                ->where('p.id != :currentProfileId')
                ->andWhere('u.isVerified = :isVerified')
                ->andWhere('p.username IS NOT NULL')
                ->andWhere('p.displayName IS NOT NULL')
                ->andWhere('p.job IS NOT NULL')
                ->setParameter('currentProfileId', $currentProfile->getId())
                ->setParameter('isVerified', true)
                ->orderBy('p.createdAt', 'DESC')
                ->setMaxResults(6)
                ->getQuery()
                ->getResult();
        }

        return $this->render('following/following.html.twig', [
            'followedProfiles' => $followedProfiles,
            'posts' => $posts,
            'suggestedProfiles' => $suggestedProfiles,
        ]);
    }
}