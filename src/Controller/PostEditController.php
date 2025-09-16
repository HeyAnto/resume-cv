<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Post;
use App\Entity\User;
use App\Form\PostFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile')]
final class PostEditController extends AbstractController
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

        // Redirect to public profile
        if ($profile->getUsername() !== $username) {
            return $this->redirectToRoute('app_profile', ['username' => $username]);
        }

        return null;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/post', name: 'app_post_list')]
    public function postList(string $username, EntityManagerInterface $entityManager): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();

        // Posts sorted -> most recent
        $posts = $entityManager->getRepository(Post::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('profile-edit/post/post-list.html.twig', [
            'username' => $username,
            'posts' => $posts,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/post/new', name: 'app_post_new')]
    public function postNew(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, string $username): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();

        $post = new Post();
        $post->setUser($user);
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setUpdatedAt(new \DateTimeImmutable());
        $post->setIsVisible(true);

        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Validate file type
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Please upload a valid image file (JPEG, PNG, WebP, or GIF)');
                    return $this->render('profile-edit/post/post-edit.html.twig', [
                        'form' => $form,
                        'username' => $username,
                        'post' => null,
                    ]);
                }

                // Validate file size (5MB max)
                $maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', 'Image file is too large. Max size is 5MB.');
                    return $this->render('profile-edit/post/post-edit.html.twig', [
                        'form' => $form,
                        'username' => $username,
                        'post' => null,
                    ]);
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/posts',
                        $newFilename
                    );
                    $post->setImagePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image');
                }
            }

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('app_post_list', ['username' => $username]);
        }

        return $this->render('profile-edit/post/post-edit.html.twig', [
            'form' => $form,
            'username' => $username,
            'post' => null,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/post/{id}', name: 'app_post_edit', requirements: ['id' => '\d+'])]
    public function postEdit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, string $username, int $id): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();

        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post || $post->getUser() !== $user) {
            throw $this->createNotFoundException('Post not found');
        }

        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Validate file type
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Please upload a valid image file (JPEG, PNG, WebP, or GIF)');
                    return $this->render('profile-edit/post/post-edit.html.twig', [
                        'form' => $form,
                        'username' => $username,
                        'post' => $post,
                    ]);
                }

                // Validate file size (5MB max)
                $maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', 'Image file is too large. Max size is 5MB.');
                    return $this->render('profile-edit/post/post-edit.html.twig', [
                        'form' => $form,
                        'username' => $username,
                        'post' => $post,
                    ]);
                }

                // Delete old image if exists
                if ($post->getImagePath()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getImagePath();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/posts',
                        $newFilename
                    );
                    $post->setImagePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image');
                }
            }

            $post->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Post updated successfully!');
            return $this->redirectToRoute('app_post_list', ['username' => $username]);
        }

        return $this->render('profile-edit/post/post-edit.html.twig', [
            'form' => $form,
            'username' => $username,
            'post' => $post,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/post/{id}/delete', name: 'app_post_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function postDelete(EntityManagerInterface $entityManager, string $username, int $id): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();

        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post || $post->getUser() !== $user) {
            throw $this->createNotFoundException('Post not found');
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

        $this->addFlash('success', 'Post deleted successfully!');
        return $this->redirectToRoute('app_post_list', ['username' => $username]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/post/{id}/toggle-visibility', name: 'app_post_toggle_visibility', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleVisibility(EntityManagerInterface $entityManager, string $username, int $id): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        /** @var User $user */
        $user = $this->getUser();

        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post || $post->getUser() !== $user) {
            throw $this->createNotFoundException('Post not found');
        }

        $post->setIsVisible(!$post->isVisible());
        $post->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $status = $post->isVisible() ? 'visible' : 'hidden';
        $this->addFlash('success', "Post is now {$status}!");

        return $this->redirectToRoute('app_post_list', ['username' => $username]);
    }
}
