<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function userList(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->query->get('search');
        $createdAt = $request->query->get('created_at');
        $updatedAt = $request->query->get('updated_at');

        // Convert string dates to DateTime objects
        $createdAfter = $createdAt ? new \DateTime($createdAt) : null;
        $updatedAfter = $updatedAt ? new \DateTime($updatedAt) : null;

        // Use the search method or fallback to findAll
        if ($search || $createdAfter || $updatedAfter) {
            $users = $userRepository->findWithFilters($search, $createdAfter, $updatedAfter);
        } else {
            $users = $userRepository->findAll();
        }

        return $this->render('admin/users/users-list.html.twig', [
            'users' => $users,
            'search' => $search,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/posts', name: 'admin_posts_list')]
    public function postList(Request $request, PostRepository $postRepository): Response
    {
        $search = $request->query->get('search');
        $createdAt = $request->query->get('created_at');
        $updatedAt = $request->query->get('updated_at');

        // Convert string dates to DateTime objects
        $createdAfter = $createdAt ? new \DateTime($createdAt) : null;
        $updatedAfter = $updatedAt ? new \DateTime($updatedAt) : null;

        // Simple search for posts
        $queryBuilder = $postRepository->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->join('u.profile', 'pr')
            ->orderBy('p.createdAt', 'DESC');

        if ($search) {
            $queryBuilder
                ->andWhere('p.description LIKE :search OR pr.displayName LIKE :search OR pr.username LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($createdAfter) {
            $queryBuilder
                ->andWhere('p.createdAt >= :createdAfter')
                ->setParameter('createdAfter', $createdAfter);
        }

        if ($updatedAfter) {
            $queryBuilder
                ->andWhere('p.updatedAt >= :updatedAfter')
                ->setParameter('updatedAfter', $updatedAfter);
        }

        $posts = $queryBuilder->getQuery()->getResult();

        return $this->render('admin/posts/posts-list.html.twig', [
            'posts' => $posts,
            'search' => $search,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/posts/{id}/toggle-visibility', name: 'admin_post_toggle_visibility', methods: ['POST'])]
    public function togglePostVisibility(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            return new JsonResponse(['success' => false, 'message' => 'Post not found'], 404);
        }

        $post->setIsVisible(!$post->isVisible());
        $entityManager->flush();

        $status = $post->isVisible() ? 'visible' : 'hidden';

        return new JsonResponse([
            'success' => true,
            'message' => "Post is now {$status}",
            'isVisible' => $post->isVisible()
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/posts/{id}/delete', name: 'admin_post_delete', methods: ['POST'])]
    public function deletePost(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            return new JsonResponse(['success' => false, 'message' => 'Post not found'], 404);
        }

        // Delete image if exists
        if ($post->getImagePath()) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getImagePath();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $entityManager->remove($post);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }
}
