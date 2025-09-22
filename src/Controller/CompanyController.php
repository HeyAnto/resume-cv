<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use App\Form\CompanyFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


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
    $companyForm = $this->createForm(CompanyFormType::class, $company);

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
}
