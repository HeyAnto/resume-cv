<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/users', name: 'admin_users_list')]
    public function userList(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users/users-list.html.twig', [
            'users' => $users,
        ]);
    }
}
