<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueUsername extends Constraint
{
  public string $message = 'This username is already taken. Please choose another one.';

  public function getTargets(): string
  {
    return self::PROPERTY_CONSTRAINT;
  }
}
