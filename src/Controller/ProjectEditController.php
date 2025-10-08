<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Project;
use App\Entity\ResumeSection;
use App\Entity\User;
use App\Form\ProjectFormType;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/profile')]
final class ProjectEditController extends AbstractController
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

    #[Route('/{username}/project', name: 'app_project_list')]
    public function projectList(string $username, ProfileRepository $profileRepository): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        // Get user and profile by username
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }
        $profile = $targetUser->getProfile();

        // Get existing projects
        $projects = [];
        $projectSection = null;
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getLabel() === 'Projects') {
                $projects = $section->getProjects()->toArray();
                // Sort by date desc
                usort($projects, function ($a, $b) {
                    return $b->getDate() <=> $a->getDate();
                });
                $projectSection = $section;
                break;
            }
        }

        return $this->render('profile-edit/project/project-list.html.twig', [
            'username' => $username,
            'profile' => $profile,
            'projects' => $projects,
            'projectSection' => $projectSection,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/project/new', name: 'app_project_new')]
    public function projectNew(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProfileRepository $profileRepository, string $username): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        // Get user and profile by username
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }
        $profile = $targetUser->getProfile();

        $project = new Project();

        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Find Projects section
            $projectSection = null;
            foreach ($profile->getResumeSections() as $section) {
                if ($section->getLabel() === 'Projects') {
                    $projectSection = $section;
                    break;
                }
            }

            // Create section if it doesn't exist
            if (!$projectSection) {
                $projectSection = new ResumeSection();
                $projectSection->setLabel('Projects');
                $projectSection->setProfile($profile);
                $projectSection->setIsVisible(true);
                $projectSection->setCreatedAt(new \DateTimeImmutable());
                $projectSection->setUpdatedAt(new \DateTimeImmutable());
                // Set order (after existing sections)
                $maxOrder = 0;
                foreach ($profile->getResumeSections() as $section) {
                    if ($section->getOrderIndex() > $maxOrder) {
                        $maxOrder = $section->getOrderIndex();
                    }
                }
                $projectSection->setOrderIndex($maxOrder + 1);
                $entityManager->persist($projectSection);
            }

            $project->setResumeSection($projectSection);
            $project->setCreatedAt(new \DateTimeImmutable());
            $project->setUpdatedAt(new \DateTimeImmutable());

            // Handle image uploads
            $this->handleImageUploads($form, $project, $slugger);

            $entityManager->persist($project);
            $profile->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Project added successfully!');
            return $this->redirectToRoute('app_project_list', ['username' => $username]);
        }

        return $this->render('profile-edit/project/project-edit.html.twig', [
            'form' => $form,
            'username' => $username,
            'profile' => $profile,
            'project' => null,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/project/{id}', name: 'app_project_edit', requirements: ['id' => '\d+'])]
    public function projectEdit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProfileRepository $profileRepository, string $username, int $id): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        // Get user and profile by username
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }
        $profile = $targetUser->getProfile();

        // Get project
        $project = $entityManager->getRepository(Project::class)->find($id);

        if (!$project || $project->getResumeSection()->getProfile() !== $profile) {
            throw $this->createNotFoundException('Project not found');
        }

        $form = $this->createForm(ProjectFormType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image uploads
            $this->handleImageUploads($form, $project, $slugger);

            $project->setUpdatedAt(new \DateTimeImmutable());
            $profile->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Project updated successfully!');
            return $this->redirectToRoute('app_project_list', ['username' => $username]);
        }

        return $this->render('profile-edit/project/project-edit.html.twig', [
            'form' => $form,
            'username' => $username,
            'profile' => $profile,
            'project' => $project,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/project/{id}/delete', name: 'app_project_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function projectDelete(EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username, int $id): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        // Get user and profile by username
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }
        $profile = $targetUser->getProfile();

        $project = $entityManager->getRepository(Project::class)->find($id);

        if (!$project || $project->getResumeSection()->getProfile() !== $profile) {
            throw $this->createNotFoundException('Project not found');
        }

        // Delete project images if they exist
        $this->deleteProjectImages($project);

        $resumeSection = $project->getResumeSection();
        $entityManager->remove($project);

        // Check remaining projects
        $remainingProjects = $resumeSection->getProjects()->count() - 1;

        if ($remainingProjects === 0) {
            $entityManager->remove($resumeSection);
        }

        $profile->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Project deleted successfully!');
        return $this->redirectToRoute('app_project_list', ['username' => $username]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/project/toggle-visibility', name: 'app_project_toggle_visibility', methods: ['POST'])]
    public function toggleVisibility(EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        // Get user and profile by username
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }
        $profile = $targetUser->getProfile();

        // Find project section
        $projectSection = null;
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getLabel() === 'Projects') {
                $projectSection = $section;
                break;
            }
        }

        if ($projectSection) {
            $projectSection->setIsVisible(!$projectSection->isVisible());
            $profile->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $message = $projectSection->isVisible() ? 'Projects section is now visible' : 'Projects section is now hidden';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_project_list', ['username' => $username]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/project/change-order', name: 'app_project_change_order', methods: ['POST'])]
    public function changeOrder(Request $request, EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        // Get user and profile by username
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }
        $profile = $targetUser->getProfile();

        $direction = $request->request->get('direction');

        // Find project section
        $projectSection = null;
        foreach ($profile->getResumeSections() as $section) {
            if ($section->getLabel() === 'Projects') {
                $projectSection = $section;
                break;
            }
        }

        if (!$projectSection) {
            $this->addFlash('error', 'Projects section not found');
            return $this->redirectToRoute('app_project_list', ['username' => $username]);
        }

        $currentOrder = $projectSection->getOrderIndex();

        // Get all sections sorted by order
        $sections = $profile->getResumeSections()->toArray();
        usort($sections, function ($a, $b) {
            return $a->getOrderIndex() <=> $b->getOrderIndex();
        });

        $currentIndex = array_search($projectSection, $sections);

        if ($direction === 'up' && $currentIndex > 0) {
            $previousSection = $sections[$currentIndex - 1];
            $tempOrder = $projectSection->getOrderIndex();
            $projectSection->setOrderIndex($previousSection->getOrderIndex());
            $previousSection->setOrderIndex($tempOrder);
        } elseif ($direction === 'down' && $currentIndex < count($sections) - 1) {
            $nextSection = $sections[$currentIndex + 1];
            $tempOrder = $projectSection->getOrderIndex();
            $projectSection->setOrderIndex($nextSection->getOrderIndex());
            $nextSection->setOrderIndex($tempOrder);
        }

        $profile->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->redirectToRoute('app_project_list', ['username' => $username]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/{username}/project/{id}/remove-image/{imageField}', name: 'app_project_remove_image', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeImage(EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username, int $id, string $imageField): Response
    {
        $usernameCheck = $this->checkUsernameAccess($username);
        if ($usernameCheck) {
            return $usernameCheck;
        }

        // Validate image field parameter
        $allowedFields = ['imagePath', 'imagePath2', 'imagePath3'];
        if (!in_array($imageField, $allowedFields)) {
            throw $this->createNotFoundException('Invalid image field');
        }

        // Get user and profile by username
        $targetUser = $profileRepository->findUserByUsername($username);

        if (!$targetUser) {
            throw $this->createNotFoundException('User not found');
        }
        $profile = $targetUser->getProfile();

        // Get project
        $project = $entityManager->getRepository(Project::class)->find($id);

        if (!$project || $project->getResumeSection()->getProfile() !== $profile) {
            throw $this->createNotFoundException('Project not found');
        }

        // Get current image path
        $getter = 'get' . ucfirst($imageField);
        if (!method_exists($project, $getter)) {
            throw $this->createNotFoundException('Invalid image field method');
        }

        $currentImagePath = $project->$getter();

        if ($currentImagePath) {
            // Delete physical file
            $fullPath = $this->getParameter('kernel.project_dir') . '/public/uploads/projects/' . $currentImagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Clear image path in database
            $setter = 'set' . ucfirst($imageField);
            if (method_exists($project, $setter)) {
                $project->$setter(null);
            }

            $project->setUpdatedAt(new \DateTimeImmutable());
            $profile->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Image removed successfully!');
        } else {
            $this->addFlash('info', 'No image to remove');
        }

        return $this->redirectToRoute('app_project_edit', ['username' => $username, 'id' => $id]);
    }

    private function deleteProjectImages(Project $project): void
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadDir = $projectDir . '/public/uploads/projects/';

        // Delete all project images
        $imageFields = ['imagePath', 'imagePath2', 'imagePath3'];

        foreach ($imageFields as $field) {
            $getter = 'get' . ucfirst($field);
            if (method_exists($project, $getter)) {
                $imagePath = $project->$getter();
                if ($imagePath) {
                    $fullPath = $uploadDir . $imagePath;
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }
        }
    }

    private function handleImageUploads($form, Project $project, SluggerInterface $slugger): void
    {

        // Handle imagePath (Image 1)
        /** @var UploadedFile|null $imageFile1 */
        $imageFile1 = $form->get('imagePath')->getData();
        if ($imageFile1) {
            $this->uploadImage($imageFile1, $project, 'imagePath', $slugger);
        }

        // Handle imagePath2 (Image 2)
        /** @var UploadedFile|null $imageFile2 */
        $imageFile2 = $form->get('imagePath2')->getData();
        if ($imageFile2) {
            $this->uploadImage($imageFile2, $project, 'imagePath2', $slugger);
        }

        // Handle imagePath3 (Image 3)
        /** @var UploadedFile|null $imageFile3 */
        $imageFile3 = $form->get('imagePath3')->getData();
        if ($imageFile3) {
            $this->uploadImage($imageFile3, $project, 'imagePath3', $slugger);
        }
    }

    private function uploadImage(UploadedFile $imageFile, Project $project, string $property, $slugger): void
    {
        // Note: File validation is now handled by the form constraints

        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

        try {
            $imageFile->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/projects',
                $newFilename
            );

            // Delete old image if it exists
            $getter = 'get' . ucfirst($property);
            if (method_exists($project, $getter)) {
                $oldPath = $project->$getter();
                if ($oldPath) {
                    $oldFile = $this->getParameter('kernel.project_dir') . '/public/uploads/projects/' . $oldPath;
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
            }

            // Set new image path
            $setter = 'set' . ucfirst($property);
            if (method_exists($project, $setter)) {
                $project->$setter($newFilename);
            }
        } catch (FileException $e) {
            $this->addFlash('error', 'Error uploading image: ' . $e->getMessage());
        }
    }
}
