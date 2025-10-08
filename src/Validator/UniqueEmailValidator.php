<?php

namespace App\Validator;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueEmailValidator extends ConstraintValidator
{
  public function __construct(
    private UserRepository $userRepository
  ) {}

  public function validate($value, Constraint $constraint): void
  {
    if (!$constraint instanceof UniqueEmail) {
      throw new UnexpectedTypeException($constraint, UniqueEmail::class);
    }

    if (null === $value || '' === $value) {
      return;
    }

    // Get current user
    $currentUser = $this->context->getRoot()->getData();
    $currentUserId = null;

    if ($currentUser instanceof User) {
      $currentUserId = $currentUser->getId();
    }

    // Check existing email
    $existingUser = $this->userRepository->findOneBy(['email' => $value]);

    // Email available
    if (!$existingUser) {
      return;
    }

    // Same user, OK
    if ($currentUserId && $existingUser->getId() === $currentUserId) {
      return;
    }

    // Email taken
    $this->context->buildViolation($constraint->message)
      ->addViolation();
  }
}
