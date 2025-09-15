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

    // Get current profile
    $currentProfile = $this->context->getRoot()->getData();
    $currentProfileId = null;

    if ($currentProfile instanceof Profile) {
      $currentProfileId = $currentProfile->getId();
    }

    // Check existing username
    $existingProfile = $this->profileRepository->findOneBy(['username' => $value]);

    // Username available
    if (!$existingProfile) {
      return;
    }

    // Same profile, OK
    if ($currentProfileId && $existingProfile->getId() === $currentProfileId) {
      return;
    }

    // Username taken
    $this->context->buildViolation($constraint->message)
      ->addViolation();
  }
}
