<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\CompanyRepository;
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
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Convert string dates to DateTime objects
        $createdAfter = $createdAt ? new \DateTime($createdAt) : null;
        $updatedAfter = $updatedAt ? new \DateTime($updatedAt) : null;

        // Get current admin user to exclude from results
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Build base query
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.profile', 'p')
            ->addSelect('p')
            ->where('u.id != :currentUserId')
            ->setParameter('currentUserId', $currentUser->getId())
            ->orderBy('u.id', 'DESC');

        // Apply filters
        if ($search) {
            $queryBuilder
                ->andWhere('u.email LIKE :search OR p.displayName LIKE :search OR p.username LIKE :search OR p.job LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($createdAfter) {
            $queryBuilder
                ->andWhere('u.createdAt >= :createdAfter')
                ->setParameter('createdAfter', $createdAfter);
        }

        if ($updatedAfter) {
            $queryBuilder
                ->andWhere('u.updatedAt >= :updatedAfter')
                ->setParameter('updatedAfter', $updatedAfter);
        }

        // Count total results
        $totalUsers = (clone $queryBuilder)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = (int) ceil($totalUsers / $limit);

        // Get paginated results
        $users = $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->render('admin/users/users-list.html.twig', [
            'users' => $users,
            'search' => $search,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'routeName' => 'admin_users_list',
            'routeParams' => array_filter([
                'search' => $search,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt
            ])
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

    #[Route('/users/{id}/toggle-admin-role', name: 'admin_user_toggle_admin_role', methods: ['POST'])]
    public function toggleUserAdminRole(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        // Ne pas permettre de se retirer le rôle admin à soi-même
        if ($user === $this->getUser()) {
            return new JsonResponse(['success' => false, 'message' => 'You cannot modify your own admin role'], 403);
        }

        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        if ($isAdmin) {
            // Remove ADMIN ROLE
            $roles = array_diff($roles, ['ROLE_ADMIN']);
            $user->setRoles(array_values($roles));
            $status = 'removed';
        } else {
            // Add ADMIN ROLE
            if (!in_array('ROLE_ADMIN', $roles)) {
                $roles[] = 'ROLE_ADMIN';
                $user->setRoles($roles);
            }
            $status = 'added';
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => "Admin role {$status} successfully",
            'isAdmin' => in_array('ROLE_ADMIN', $user->getRoles())
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('admin_users_list');
        }

        // User cannot delete themselves
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot delete your own account');
            return $this->redirectToRoute('admin_users_list');
        }

        // Delete profile picture
        if (
            $user->getProfile() && $user->getProfile()->getProfilePictureUrl() &&
            $user->getProfile()->getProfilePictureUrl() !== 'images/img_default_user.webp'
        ) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/' . $user->getProfile()->getProfilePictureUrl();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User deleted successfully');
        return $this->redirectToRoute('admin_users_list');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/posts', name: 'admin_posts_list')]
    public function postList(Request $request, PostRepository $postRepository): Response
    {
        $search = $request->query->get('search');
        $createdAt = $request->query->get('created_at');
        $updatedAt = $request->query->get('updated_at');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Convert string dates to DateTime objects
        $createdAfter = $createdAt ? new \DateTime($createdAt) : null;
        $updatedAfter = $updatedAt ? new \DateTime($updatedAt) : null;

        // Get current admin user to exclude their posts from results
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Simple search for posts
        $queryBuilder = $postRepository->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->join('u.profile', 'pr')
            ->where('u.id != :currentUserId')
            ->setParameter('currentUserId', $currentUser->getId())
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

        // Count total results
        $totalPosts = (clone $queryBuilder)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = (int) ceil($totalPosts / $limit);

        // Get paginated results
        $posts = $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->render('admin/posts/posts-list.html.twig', [
            'posts' => $posts,
            'search' => $search,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'routeName' => 'admin_posts_list',
            'routeParams' => array_filter([
                'search' => $search,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt
            ])
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/companies', name: 'admin_companies_list')]
    public function companyList(Request $request, CompanyRepository $companyRepository): Response
    {
        $search = $request->query->get('search');
        $createdAt = $request->query->get('created_at');
        $updatedAt = $request->query->get('updated_at');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Convert string dates to DateTime objects
        $createdAfter = $createdAt ? new \DateTime($createdAt) : null;
        $updatedAfter = $updatedAt ? new \DateTime($updatedAt) : null;

        // Search for companies
        $queryBuilder = $companyRepository->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->orderBy('c.createdAt', 'DESC');

        if ($search) {
            $queryBuilder->andWhere('c.companyName LIKE :search OR c.location LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($createdAfter) {
            $queryBuilder->andWhere('c.createdAt >= :createdAfter')
                ->setParameter('createdAfter', $createdAfter);
        }

        if ($updatedAfter) {
            $queryBuilder->andWhere('c.updatedAt >= :updatedAfter')
                ->setParameter('updatedAfter', $updatedAfter);
        }

        // Count total results
        $totalCompanies = (clone $queryBuilder)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = (int) ceil($totalCompanies / $limit);

        // Get paginated results
        $companies = $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->render('admin/companies/companies-list.html.twig', [
            'companies' => $companies,
            'search' => $search,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'routeName' => 'admin_companies_list',
            'routeParams' => array_filter([
                'search' => $search,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt
            ])
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/companies/{id}/delete', name: 'admin_company_delete', methods: ['POST'])]
    public function deleteCompany(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $company = $entityManager->getRepository(Company::class)->find($id);

        if (!$company) {
            return new JsonResponse(['success' => false, 'message' => 'Company not found'], 404);
        }

        // Delete company logo if not default
        $currentPicturePath = $company->getProfilePicturePath();
        if ($currentPicturePath && $currentPicturePath !== 'images/img_default_company.webp') {
            $pictureFile = $this->getParameter('kernel.project_dir') . '/public/' . $currentPicturePath;
            if (file_exists($pictureFile)) {
                unlink($pictureFile);
            }
        }

        $entityManager->remove($company);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Company deleted successfully'
        ]);
    }
}
