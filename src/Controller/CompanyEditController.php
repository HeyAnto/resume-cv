<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Company;
use App\Entity\CompanyTag;
use App\Entity\User;
use App\Form\CompanyFormType;
use App\Form\CompanyTagFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/company')]
final class CompanyEditController extends AbstractController
{
  use UserRedirectionTrait;

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  private function checkCompanyAccess(Company $company): ?Response
  {
    /** @var User $user */
    $user = $this->getUser();

    // Allow access if admin or owner
    if ($this->isGranted('ROLE_ADMIN') || $company->getUser() === $user) {
      return null;
    }

    // Redirect to public company profile
    return $this->redirectToRoute('app_company_profile', [
      'id' => $company->getId(),
      'companyName' => $company->getSlug()
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{id}-{companyName}/edit', name: 'app_company_edit')]
  public function companyEdit(int $id, string $companyName, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
  {
    $userCheck = $this->checkUserAccess();
    if ($userCheck) {
      return $userCheck;
    }

    /** @var User $currentUser */
    $currentUser = $this->getUser();

    // Find the company
    $company = $entityManager->getRepository(Company::class)->find($id);

    if (!$company) {
      throw $this->createNotFoundException('Company not found');
    }

    // Check edit access
    $companyAccessCheck = $this->checkCompanyAccess($company);
    if ($companyAccessCheck) {
      return $companyAccessCheck;
    }

    // Create form
    $companyForm = $this->createForm(CompanyFormType::class, $company, ['submit_label' => 'Done']);

    // Handle form submission
    if ($request->isMethod('POST')) {
      // First handle the main form data if present
      $companyForm->handleRequest($request);
      if ($companyForm->isSubmitted() && $companyForm->isValid()) {
        $company->setUpdatedAt(new \DateTimeImmutable());
      }

      // Handle picture upload if present and preserve form data
      if ($request->files->has('profilePicture')) {
        // Preserve form data during image upload
        if ($request->request->has('preserve_companyName') && $request->request->get('preserve_companyName')) {
          $company->setCompanyName($request->request->get('preserve_companyName'));
        }
        if ($request->request->has('preserve_location') && $request->request->get('preserve_location')) {
          $company->setLocation($request->request->get('preserve_location'));
        }
        if ($request->request->has('preserve_websiteName')) {
          $company->setWebsiteName($request->request->get('preserve_websiteName'));
        }
        if ($request->request->has('preserve_websiteLink')) {
          $company->setWebsiteLink($request->request->get('preserve_websiteLink'));
        }
        if ($request->request->has('preserve_description')) {
          $company->setDescription($request->request->get('preserve_description'));
        }
        $profilePictureFile = $request->files->get('profilePicture');

        if ($profilePictureFile) {
          $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
          $safeFilename = $slugger->slug($originalFilename);
          $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();

          try {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/company-logos';
            if (!is_dir($uploadsDir)) {
              mkdir($uploadsDir, 0755, true);
            }

            // Delete old image if not default
            $oldPath = $company->getProfilePicturePath();
            if ($oldPath && $oldPath !== 'images/img_default_company.webp') {
              $oldFile = $this->getParameter('kernel.project_dir') . '/public/' . $oldPath;
              if (file_exists($oldFile)) {
                unlink($oldFile);
              }
            }

            $profilePictureFile->move($uploadsDir, $newFilename);
            $company->setProfilePicturePath('uploads/company-logos/' . $newFilename);
            $company->setUpdatedAt(new \DateTimeImmutable());

            $this->addFlash('success', 'Company logo updated successfully!');
          } catch (FileException $e) {
            $this->addFlash('error', 'Error uploading logo');
          }
        }
      }

      // Save all changes and provide appropriate feedback
      if ($companyForm->isSubmitted() && $companyForm->isValid()) {
        $entityManager->flush();
        if (!$request->files->has('profilePicture')) {
          $this->addFlash('success', 'Company profile updated successfully!');
        }
        return $this->redirectToRoute('app_company_profile', ['id' => $id, 'companyName' => $company->getSlug()]);
      } elseif ($request->files->has('profilePicture')) {
        $entityManager->flush();
        return $this->redirectToRoute('app_company_edit', ['id' => $id, 'companyName' => $company->getSlug()]);
      }
    }

    return $this->render('company/profile/company-edit-profile.html.twig', [
      'company' => $company,
      'companyForm' => $companyForm,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{id}-{companyName}/edit/tags', name: 'app_company_edit_tags')]
  public function companyEditTags(int $id, string $companyName, Request $request, EntityManagerInterface $entityManager): Response
  {
    $userCheck = $this->checkUserAccess();
    if ($userCheck) {
      return $userCheck;
    }

    // Find the company
    $company = $entityManager->getRepository(Company::class)->find($id);

    if (!$company) {
      throw $this->createNotFoundException('Company not found');
    }

    // Check edit access
    $companyAccessCheck = $this->checkCompanyAccess($company);
    if ($companyAccessCheck) {
      return $companyAccessCheck;
    }

    // Handle tag deletion
    if ($request->isMethod('POST') && $request->request->has('delete_tag')) {
      $tagId = $request->request->get('delete_tag');
      $tag = $entityManager->getRepository(CompanyTag::class)->find($tagId);

      if ($tag && $tag->getCompany() === $company) {
        $entityManager->remove($tag);
        $entityManager->flush();
        $this->addFlash('success', 'Tag deleted successfully!');
      }

      return $this->redirectToRoute('app_company_edit_tags', ['id' => $id, 'companyName' => $company->getSlug()]);
    }

    // Handle tag creation
    $newTag = new CompanyTag();
    $tagForm = $this->createForm(CompanyTagFormType::class, $newTag);
    $tagForm->handleRequest($request);

    if ($tagForm->isSubmitted() && $tagForm->isValid()) {
      // Check if company already has 4 tags
      if ($company->getCompanyTags()->count() >= 4) {
        $this->addFlash('error', 'Maximum 4 tags allowed per company.');
        return $this->redirectToRoute('app_company_edit_tags', ['id' => $id, 'companyName' => $company->getSlug()]);
      }

      // Check if tag already exists for this company
      $existingTag = $entityManager->getRepository(CompanyTag::class)->findOneBy([
        'company' => $company,
        'label' => $newTag->getLabel()
      ]);

      if ($existingTag) {
        $this->addFlash('error', 'This tag already exists for your company.');
        return $this->redirectToRoute('app_company_edit_tags', ['id' => $id, 'companyName' => $company->getSlug()]);
      }

      $newTag->setCompany($company);
      $newTag->setCreatedAt(new \DateTimeImmutable());
      $newTag->setUpdatedAt(new \DateTimeImmutable());

      $entityManager->persist($newTag);
      $entityManager->flush();

      $this->addFlash('success', 'Tag created successfully!');
      return $this->redirectToRoute('app_company_edit_tags', ['id' => $id, 'companyName' => $company->getSlug()]);
    }

    return $this->render('company/profile/company-edit-tags.html.twig', [
      'company' => $company,
      'tagForm' => $tagForm,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{id}-{companyName}/edit/remove-picture', name: 'app_company_remove_picture', methods: ['POST'])]
  public function removeCompanyPicture(int $id, string $companyName, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
  {
    $userCheck = $this->checkUserAccess();
    if ($userCheck) {
      return $userCheck;
    }

    /** @var User $currentUser */
    $currentUser = $this->getUser();

    // Find the company
    $company = $entityManager->getRepository(Company::class)->find($id);

    if (!$company) {
      throw $this->createNotFoundException('Company not found');
    }

    // Check edit access
    $companyAccessCheck = $this->checkCompanyAccess($company);
    if ($companyAccessCheck) {
      return $companyAccessCheck;
    }

    // Preserve form data during image removal
    if ($request->request->has('preserve_companyName') && $request->request->get('preserve_companyName')) {
      $company->setCompanyName($request->request->get('preserve_companyName'));
    }
    if ($request->request->has('preserve_location') && $request->request->get('preserve_location')) {
      $company->setLocation($request->request->get('preserve_location'));
    }
    if ($request->request->has('preserve_websiteName')) {
      $company->setWebsiteName($request->request->get('preserve_websiteName'));
    }
    if ($request->request->has('preserve_websiteLink')) {
      $company->setWebsiteLink($request->request->get('preserve_websiteLink'));
    }
    if ($request->request->has('preserve_description')) {
      $company->setDescription($request->request->get('preserve_description'));
    }

    // Delete current picture if not default
    $currentPicturePath = $company->getProfilePicturePath();
    if ($currentPicturePath && $currentPicturePath !== 'images/img_default_company.webp') {
      $pictureFile = $this->getParameter('kernel.project_dir') . '/public/' . $currentPicturePath;
      if (file_exists($pictureFile)) {
        unlink($pictureFile);
      }
    }

    // Set to default
    $company->setProfilePicturePath('images/img_default_company.webp');
    $company->setUpdatedAt(new \DateTimeImmutable());

    $entityManager->flush();

    $this->addFlash('success', 'Company logo removed successfully!');
    return $this->redirectToRoute('app_company_edit', ['id' => $id, 'companyName' => $company->getSlug()]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{id}-{companyName}/edit/delete', name: 'app_company_delete', methods: ['POST'])]
  public function deleteCompany(int $id, string $companyName, EntityManagerInterface $entityManager): Response
  {
    $userCheck = $this->checkUserAccess();
    if ($userCheck) {
      return $userCheck;
    }

    /** @var User $currentUser */
    $currentUser = $this->getUser();

    // Find the company
    $company = $entityManager->getRepository(Company::class)->find($id);

    if (!$company) {
      throw $this->createNotFoundException('Company not found');
    }

    // Check delete access
    $companyAccessCheck = $this->checkCompanyAccess($company);
    if ($companyAccessCheck) {
      return $companyAccessCheck;
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

    $this->addFlash('success', 'Company deleted successfully!');
    return $this->redirectToRoute('app_company_list');
  }
}
