<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Profile;
use App\Form\RegistrationFormType;
use App\Form\ReProfileFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

#[Route('/connection/register')]
class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier) {}

    #[Route('', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        // If user is already authenticated, redirect based on their status
        if ($this->getUser()) {
            return $this->redirectBasedOnUserStatus();
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Set initial role for unverified user
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@resume.cv', 'Resume.cv'))
                    ->to((string) $user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // Login the user and redirect to email verification pending
            $security->login($user, 'form_login', 'main');
            return $this->redirectToRoute('app_verify_email_pending');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
    #[Route('/verify', name: 'app_verify_email_pending')]
    public function verifyUserEmailPending(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_register');
        }

        // If user is already verified, redirect to profile completion
        if ($user instanceof User && $user->isVerified()) {
            return $this->redirectToRoute('app_profile_complete');
        }

        return $this->render('registration/verify_pending.html.twig');
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_profile_complete');
    }

    #[Route('/profile-complete', name: 'app_profile_complete')]
    public function profileComplete(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_register');
        }

        // If user is not verified
        if (!$user->isVerified()) {
            return $this->redirectToRoute('app_verify_email_pending');
        }

        // If profile is already complete
        if ($user->isProfileComplete()) {
            $this->addFlash('info', 'Your profile is already complete.');
            return $this->redirectToRoute('app_home');
        }

        // Create or get existing profile
        $profile = $user->getProfile();
        if (!$profile) {
            $profile = new Profile();
            $profile->setCreatedAt(new \DateTimeImmutable());
        }
        $profile->setUpdatedAt(new \DateTimeImmutable());

        $form = $this->createForm(ReProfileFormType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associate profile with user
            if (!$user->getProfile()) {
                $user->setProfile($profile);
            }

            // Update user role to complete
            $user->setRoles(['ROLE_USER_COMPLETE']);

            $entityManager->persist($profile);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your profile has been completed successfully! Welcome to Resume.cv');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/profile_complete.html.twig', [
            'profileForm' => $form,
        ]);
    }

    private function redirectBasedOnUserStatus(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_register');
        }

        // If user has complete profile
        if ($user->hasRole('ROLE_USER_COMPLETE')) {
            return $this->redirectToRoute('app_home');
        }

        // If user is verified but profile incomplete
        if ($user->isVerified() && !$user->isProfileComplete()) {
            return $this->redirectToRoute('app_profile_complete');
        }

        // If user is not verified
        if (!$user->isVerified()) {
            return $this->redirectToRoute('app_verify_email_pending');
        }

        // Default fallback
        return $this->redirectToRoute('app_home');
    }
}
