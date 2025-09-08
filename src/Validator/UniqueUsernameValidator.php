<?php

namespace App\Validator;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUsernameValidator extends ConstraintValidator
{
  public function __construct(
    private ProfileRepository $profileRepository
  ) {}

  public function validate($value, Constraint $constraint): void
  {
    if (!$constraint instanceof UniqueUsername) {
      throw new UnexpectedTypeException($constraint, UniqueUsername::class);
    }

    if (null === $value || '' === $value) {
      return;
    }

    // Get the current profile being validated (if editing existing profile)
    $currentProfile = $this->context->getObject();
    $currentProfileId = null;

    if ($currentProfile instanceof Profile && $currentProfile->getId()) {
      $currentProfileId = $currentProfile->getId();
    }

    // Check if username already exists (excluding current profile if editing)
    $existingProfile = $this->profileRepository->findOneBy(['username' => $value]);

    if ($existingProfile && $existingProfile->getId() !== $currentProfileId) {
      $this->context->buildViolation($constraint->message)
        ->addViolation();
    }
  }
}
