<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use App\Form\CompanyFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/company')]
final class CompanyController extends AbstractController
{
  private function checkUserAccess(): ?Response
  {
    // Check if user is logged in
    if (!$this->getUser()) {
      return $this->redirectToRoute('app_login');
    }

    /** @var User $user */
    $user = $this->getUser();
    $profile = $user->getProfile();

    // Check if profile is complete
    if (!$profile || !$profile->getUsername()) {
      return $this->redirectToRoute('app_profile_edit');
    }

    return null;
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('', name: 'app_company_list')]
  public function companyList(EntityManagerInterface $entityManager): Response
  {
    $userCheck = $this->checkUserAccess();
    if ($userCheck) {
      return $userCheck;
    }

    /** @var User $currentUser */
    $currentUser = $this->getUser();

    // Get user's companies
    $companies = $entityManager->getRepository(Company::class)
      ->findBy(['user' => $currentUser], ['createdAt' => 'DESC']);

    return $this->render('company/company-list.html.twig', [
      'companies' => $companies,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/new', name: 'app_company_new')]
  public function companyNew(Request $request, EntityManagerInterface $entityManager): Response
  {
    $userCheck = $this->checkUserAccess();
    if ($userCheck) {
      return $userCheck;
    }

    /** @var User $currentUser */
    $currentUser = $this->getUser();

    $company = new Company();

    // Create form
    $companyForm = $this->createForm(CompanyFormType::class, $company, ['submit_label' => 'Create Company']);

    // Handle company form submission
    $companyForm->handleRequest($request);
    if ($companyForm->isSubmitted() && $companyForm->isValid()) {
      // Set default company image
      $company->setProfilePicturePath('images/img_default_company.webp');
      $company->setUser($currentUser);
      $company->setCreatedAt(new \DateTimeImmutable());
      $company->setUpdatedAt(new \DateTimeImmutable());

      $entityManager->persist($company);
      $entityManager->flush();

      $this->addFlash('success', 'Company created successfully!');
      return $this->redirectToRoute('app_company_list');
    }

    return $this->render('company/company-new.html.twig', [
      'companyForm' => $companyForm,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{id}-{companyName}', name: 'app_company_profile')]
  public function companyProfile(int $id, string $companyName, EntityManagerInterface $entityManager): Response
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

    return $this->render('company/profile/company-profile.html.twig', [
      'company' => $company,
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
    $canEdit = ($company->getUser() === $currentUser) || $this->isGranted('ROLE_ADMIN');
    if (!$canEdit) {
      throw $this->createAccessDeniedException('You are not authorized to edit this company');
    }

    // Create form
    $companyForm = $this->createForm(CompanyFormType::class, $company, ['submit_label' => 'Done']);

    // Picture upload
    if ($request->isMethod('POST') && $request->files->has('profilePicture')) {
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

          $entityManager->flush();
          $this->addFlash('success', 'Company logo updated successfully!');
        } catch (FileException $e) {
          $this->addFlash('error', 'Error uploading logo');
        }
      }

      return $this->redirectToRoute('app_company_edit', ['id' => $id, 'companyName' => $company->getSlug()]);
    }

    // Handle company form submission
    $companyForm->handleRequest($request);
    if ($companyForm->isSubmitted() && $companyForm->isValid()) {
      $company->setUpdatedAt(new \DateTimeImmutable());

      $entityManager->flush();
      $this->addFlash('success', 'Company profile updated successfully!');

      return $this->redirectToRoute('app_company_profile', ['id' => $id, 'companyName' => $company->getSlug()]);
    }

    return $this->render('company/profile/company-edit-profile.html.twig', [
      'company' => $company,
      'companyForm' => $companyForm,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{id}-{companyName}/remove-picture', name: 'app_company_remove_picture')]
  public function removeCompanyPicture(int $id, string $companyName, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
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

    // Check edit access: owner can edit, admin can edit all
    $canEdit = ($company->getUser() === $currentUser) || $this->isGranted('ROLE_ADMIN');
    if (!$canEdit) {
      throw $this->createAccessDeniedException('You are not authorized to edit this company');
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

  #[Route('/{id}-{companyName}/delete', name: 'app_company_delete', methods: ['POST'])]
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

    $canDelete = ($company->getUser() === $currentUser) || $this->isGranted('ROLE_ADMIN');
    if (!$canDelete) {
      throw $this->createAccessDeniedException('You are not authorized to delete this company');
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
