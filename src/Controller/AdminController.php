<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_index')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_users_list');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/users', name: 'admin_users_list')]
    public function userList(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users/users-list.html.twig', [
            'users' => $users,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/users/{id}/verify', name: 'admin_user_verify', methods: ['POST'])]
    public function verifyUser(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->setIsVerified(true);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'User verified successfully']);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/users/{id}/unverify', name: 'admin_user_unverify', methods: ['POST'])]
    public function unverifyUser(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->setIsVerified(false);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'User unverified successfully']);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/users/{id}/toggle-verified', name: 'admin_user_toggle_verified', methods: ['POST'])]
    public function toggleUserVerified(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->setIsVerified(!$user->isVerified());
        $entityManager->flush();

        $status = $user->isVerified() ? 'verified' : 'unverified';

        return new JsonResponse([
            'success' => true,
            'message' => "User {$status} successfully",
            'isVerified' => $user->isVerified()
        ]);
    }
}
