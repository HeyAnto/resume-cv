<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\JobOffer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/job')]
#[IsGranted('ROLE_USER')]
final class JobOfferController extends AbstractController
{
  #[Route('/{id}-{companySlug}', name: 'app_company_jobs')]
  public function companyJobs(int $id, string $companySlug, EntityManagerInterface $entityManager): Response
  {
    // Verify user is authenticated, verified and has completed profile
    /** @var User $user */
    $user = $this->getUser();
    if (!$user || !$user->isVerified() || !$user->isProfileComplete()) {
      throw $this->createAccessDeniedException('Access denied. Please verify your email and complete your profile.');
    }

    // Find the company
    $company = $entityManager->getRepository(Company::class)->find($id);

    if (!$company) {
      throw $this->createNotFoundException('Company not found');
    }

    // Get all job offers for this company
    $jobOffers = $entityManager->getRepository(JobOffer::class)->findBy(
      ['company' => $company],
      ['createdAt' => 'DESC']
    );

    return $this->render('company/company-jobs-list.html.twig', [
      'company' => $company,
      'jobOffers' => $jobOffers,
    ]);
  }

  #[Route('/{id}-{companySlug}/{jobId}', name: 'app_job_offer_view')]
  public function viewJobOffer(int $id, string $companySlug, int $jobId, EntityManagerInterface $entityManager): Response
  {
    // Verify user is authenticated, verified and has completed profile
    /** @var User $user */
    $user = $this->getUser();
    if (!$user || !$user->isVerified() || !$user->isProfileComplete()) {
      throw $this->createAccessDeniedException('Access denied. Please verify your email and complete your profile.');
    }

    // Find the company
    $company = $entityManager->getRepository(Company::class)->find($id);

    if (!$company) {
      throw $this->createNotFoundException('Company not found');
    }

    // Find the job offer
    $jobOffer = $entityManager->getRepository(JobOffer::class)->findOneBy([
      'id' => $jobId,
      'company' => $company
    ]);

    if (!$jobOffer) {
      throw $this->createNotFoundException('Job offer not found');
    }

    return $this->render('company/company-job.html.twig', [
      'company' => $company,
      'jobOffer' => $jobOffer,
    ]);
  }
}
