<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
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

  #[Route('/{username}', name: 'app_company_list')]
  public function companyList(string $username, EntityManagerInterface $entityManager): Response
  {
    $usernameCheck = $this->checkUsernameAccess($username);
    if ($usernameCheck) {
      return $usernameCheck;
    }

    // Get user and profile by username
    $targetUser = $entityManager->getRepository(User::class)
      ->createQueryBuilder('u')
      ->join('u.profile', 'p')
      ->where('p.username = :username')
      ->setParameter('username', $username)
      ->getQuery()
      ->getOneOrNullResult();

    if (!$targetUser) {
      throw $this->createNotFoundException('User not found');
    }

    // Get user's companies
    $companies = $entityManager->getRepository(Company::class)
      ->findBy(['user' => $targetUser], ['createdAt' => 'DESC']);

    return $this->render('company/company-list.html.twig', [
      'username' => $username,
      'companies' => $companies,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{username}/new', name: 'app_company_new')]
  public function companyNew(Request $request, EntityManagerInterface $entityManager, string $username): Response
  {
    $usernameCheck = $this->checkUsernameAccess($username);
    if ($usernameCheck) {
      return $usernameCheck;
    }

    // Get user and profile by username
    $targetUser = $entityManager->getRepository(User::class)
      ->createQueryBuilder('u')
      ->join('u.profile', 'p')
      ->where('p.username = :username')
      ->setParameter('username', $username)
      ->getQuery()
      ->getOneOrNullResult();

    if (!$targetUser) {
      throw $this->createNotFoundException('User not found');
    }

    $company = new Company();

    // Create form
    $companyForm = $this->createForm(CompanyFormType::class, $company);

    // Handle company form submission
    $companyForm->handleRequest($request);
    if ($companyForm->isSubmitted() && $companyForm->isValid()) {
      // Set default company image
      $company->setProfilePicturePath('images/img_default_company.webp');
      $company->setUser($targetUser);
      $company->setCreatedAt(new \DateTimeImmutable());
      $company->setUpdatedAt(new \DateTimeImmutable());

      $entityManager->persist($company);
      $entityManager->flush();

      $this->addFlash('success', 'Company created successfully!');
      return $this->redirectToRoute('app_company_list', ['username' => $username]);
    }

    return $this->render('company/company-new.html.twig', [
      'companyForm' => $companyForm,
      'username' => $username,
    ]);
  }
}
