<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueEmail extends Constraint
{
  public string $message = 'This email is already taken. Please choose another one.';

  public function getTargets(): string
  {
    return self::PROPERTY_CONSTRAINT;
  }
}
