<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\Company;
use App\Entity\JobOffer;
use App\Entity\User;
use App\Form\JobOfferFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/company')]
final class CompanyJobOfferController extends AbstractController
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

  #[Route('/{id}-{companyName}/job-offers', name: 'app_company_job_offers')]
  public function jobOffersList(int $id, string $companyName, EntityManagerInterface $entityManager): Response
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

    // Get job offers for this company
    $jobOffers = $entityManager->getRepository(JobOffer::class)->findBy(
      ['company' => $company],
      ['createdAt' => 'DESC']
    );

    return $this->render('company/profile/company-edit-job-list.html.twig', [
      'company' => $company,
      'jobOffers' => $jobOffers,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{id}-{companyName}/job-offers/new', name: 'app_company_job_offer_new')]
  public function newJobOffer(int $id, string $companyName, Request $request, EntityManagerInterface $entityManager): Response
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

    // Create new job offer
    $jobOffer = new JobOffer();
    $jobOffer->setCompany($company);

    $form = $this->createForm(JobOfferFormType::class, $jobOffer, ['submit_label' => 'Create Job Offer']);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $jobOffer->setCreatedAt(new \DateTimeImmutable());
      $jobOffer->setUpdatedAt(new \DateTimeImmutable());

      $entityManager->persist($jobOffer);
      $entityManager->flush();

      $this->addFlash('success', 'Job offer created successfully!');

      return $this->redirectToRoute('app_company_job_offers', [
        'id' => $company->getId(),
        'companyName' => $company->getSlug()
      ]);
    }

    return $this->render('company/profile/company-job-edit.html.twig', [
      'company' => $company,
      'jobOffer' => null,
      'form' => $form,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{id}-{companyName}/job-offers/{jobId}', name: 'app_company_job_offer_edit')]
  public function editJobOffer(int $id, string $companyName, int $jobId, Request $request, EntityManagerInterface $entityManager): Response
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

    // Find the job offer
    $jobOffer = $entityManager->getRepository(JobOffer::class)->findOneBy([
      'id' => $jobId,
      'company' => $company
    ]);

    if (!$jobOffer) {
      throw $this->createNotFoundException('Job offer not found');
    }

    $form = $this->createForm(JobOfferFormType::class, $jobOffer, ['submit_label' => 'Update Job Offer']);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $jobOffer->setUpdatedAt(new \DateTimeImmutable());

      $entityManager->flush();

      $this->addFlash('success', 'Job offer updated successfully!');

      return $this->redirectToRoute('app_company_job_offers', [
        'id' => $company->getId(),
        'companyName' => $company->getSlug()
      ]);
    }

    return $this->render('company/profile/company-job-edit.html.twig', [
      'company' => $company,
      'jobOffer' => $jobOffer,
      'form' => $form,
    ]);
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  #[Route('/{id}-{companyName}/job-offers/{jobId}/delete', name: 'app_company_job_offer_delete', methods: ['POST'])]
  public function deleteJobOffer(int $id, string $companyName, int $jobId, EntityManagerInterface $entityManager): Response
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

    // Find the job offer
    $jobOffer = $entityManager->getRepository(JobOffer::class)->findOneBy([
      'id' => $jobId,
      'company' => $company
    ]);

    if (!$jobOffer) {
      throw $this->createNotFoundException('Job offer not found');
    }

    $entityManager->remove($jobOffer);
    $entityManager->flush();

    $this->addFlash('success', 'Job offer deleted successfully!');

    return $this->redirectToRoute('app_company_job_offers', [
      'id' => $company->getId(),
      'companyName' => $company->getSlug()
    ]);
  }
}
