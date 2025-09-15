<?php

namespace App\Controller;

use App\Controller\Trait\UserRedirectionTrait;
use App\Entity\User;
use App\Entity\Profile;
use App\Form\RegistrationFormType;
use App\Form\RegisterProfileFormType;
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
    use UserRedirectionTrait;

    public function __construct(private EmailVerifier $emailVerifier) {}

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        if ($redirect = $this->checkAuthAccess()) {
            return $redirect;
        }

        // Prevent double submission
        if ($request->isMethod('POST') && $request->getSession()->get('form_submitted')) {
            $this->addFlash('error', 'Form already submitted. Please wait.');
            return $this->redirectToRoute('app_register');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $request->getSession()->set('form_submitted', true);

            // Check existing user
            $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $request->getSession()->remove('form_submitted');
                $this->addFlash('error', 'An account with this email already exists. Please try logging in instead.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Encode password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Set initial role
            $user->setRoles(['ROLE_USER']);

            try {
                $entityManager->persist($user);
                $entityManager->flush();

                // Clear flag
                $request->getSession()->remove('form_submitted');
            } catch (\Exception $e) {
                $request->getSession()->remove('form_submitted');
                // Handle duplicates
                if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                    $this->addFlash('error', 'An account with this email already exists. Please try logging in instead.');
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form,
                    ]);
                }
                throw $e;
            }

            // Send verification email
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@resume.cv', 'Resume.cv'))
                    ->to((string) $user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            // Login and redirect
            $security->login($user, 'form_login', 'main');
            return $this->redirectToRoute('app_verify_email_pending');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/verify', name: 'app_verify_email_pending')]
    public function verifyUserEmailPending(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_register');
        }

        if ($user instanceof User && $user->isVerified()) {
            return $this->redirectBasedOnUserStatus();
        }

        return $this->render('registration/verify_pending.html.twig');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_register');
        }

        if ($user->isVerified()) {
            return $this->redirectBasedOnUserStatus();
        }

        // Rate limiting -> per 15 minutes
        $lastSent = $request->getSession()->get('last_verification_sent');
        if ($lastSent && time() - $lastSent < 900) {
            $this->addFlash('error', 'Please wait 15 minutes before requesting another verification email.');
            return $this->redirectToRoute('app_verify_email_pending');
        }

        // Send verification email
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('no-reply@resume.cv', 'Resume.cv'))
                ->to((string) $user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );

        // Set rate limit
        $request->getSession()->set('last_verification_sent', time());

        $this->addFlash('success', 'Verification email sent! Please check your inbox.');
        return $this->redirectToRoute('app_verify_email_pending');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');
        return $this->redirectBasedOnUserStatus();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    #[Route('/complete', name: 'app_complete')]
    public function profileComplete(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_register');
        }

        if (!$user->isVerified()) {
            return $this->redirectToRoute('app_verify_email_pending');
        }

        if ($user->isProfileComplete()) {
            $this->addFlash('info', 'Your profile is already complete.');
            return $this->redirectBasedOnUserStatus();
        }

        // Prevent double submission
        if ($request->isMethod('POST') && $request->getSession()->get('profile_submitted')) {
            $this->addFlash('error', 'Profile already submitted. Please wait.');
            return $this->redirectToRoute('app_complete');
        }

        // Create profile
        $profile = $user->getProfile();
        if (!$profile) {
            $profile = new Profile();
            $profile->setCreatedAt(new \DateTimeImmutable());
        }
        $profile->setUpdatedAt(new \DateTimeImmutable());

        $form = $this->createForm(RegisterProfileFormType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mark as submitted
            $request->getSession()->set('profile_submitted', true);

            // Associate profile
            if (!$user->getProfile()) {
                $user->setProfile($profile);
            }

            // Update role
            $user->setRoles(['ROLE_USER_COMPLETE']);

            $entityManager->persist($profile);
            $entityManager->persist($user);
            $entityManager->flush();

            // Clear flag
            $request->getSession()->remove('profile_submitted');
            $security->login($user, 'form_login', 'main');

            $this->addFlash('success', 'Your profile has been completed successfully! Welcome to Resume.cv');
            return $this->redirectBasedOnUserStatus();
        }

        return $this->render('registration/profile_complete.html.twig', [
            'profileForm' => $form,
        ]);
    }
}
