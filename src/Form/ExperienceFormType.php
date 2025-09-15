<?php

namespace App\Form;

use App\Entity\Experience;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ExperienceFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('companyName', TextType::class, [
        'label' => 'Company Name',
        'attr' => [
          'placeholder' => 'Google, Microsoft, etc.',
          'class' => 'form-control',
          'data-counter' => 'companyName-counter',
          'data-max' => '32',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Company name is required']),
          new Assert\Length([
            'max' => 32,
            'maxMessage' => 'Company name cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('companyLink', UrlType::class, [
        'label' => 'Company Website',
        'required' => false,
        'attr' => [
          'placeholder' => 'https://www.example.com',
          'class' => 'form-control',
          'data-counter' => 'companyLink-counter',
          'data-max' => '98',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\Length([
            'max' => 98,
            'maxMessage' => 'Company website cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('job', TextType::class, [
        'label' => 'Job Title',
        'attr' => [
          'placeholder' => 'Full Stack Developer',
          'class' => 'form-control',
          'data-counter' => 'job-counter',
          'data-max' => '32',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Job title is required']),
          new Assert\Length([
            'max' => 32,
            'maxMessage' => 'Job title cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('location', TextType::class, [
        'label' => 'Location',
        'attr' => [
          'placeholder' => 'Paris, France',
          'class' => 'form-control',
          'data-counter' => 'location-counter',
          'data-max' => '32',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Location is required']),
          new Assert\Length([
            'max' => 32,
            'maxMessage' => 'Location cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Description (Optional)',
        'required' => false,
        'attr' => [
          'placeholder' => 'Describe your missions and achievements...',
          'class' => 'form-control',
          'rows' => 3,
          'data-counter' => 'description-counter',
          'data-max' => '120'
        ],
        'constraints' => [
          new Assert\Length([
            'max' => 120,
            'maxMessage' => 'Description cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('startDate', DateType::class, [
        'label' => 'Start Date',
        'widget' => 'single_text',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('endDate', DateType::class, [
        'label' => 'End Date (Leave empty if current)',
        'required' => false,
        'widget' => 'single_text',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('submit', SubmitType::class, [
        'label' => 'Save'
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Experience::class,
      'constraints' => [
        new Assert\Callback([$this, 'validateDates'])
      ]
    ]);
  }

  public function validateDates($experience, ExecutionContextInterface $context): void
  {
    if ($experience->getStartDate() && $experience->getEndDate()) {
      if ($experience->getStartDate() > $experience->getEndDate()) {
        $context->buildViolation('End date must be after start date')
          ->atPath('endDate')
          ->addViolation();
      }
    }

    // Check if dates are not in the future
    $now = new \DateTimeImmutable();
    if ($experience->getStartDate() && $experience->getStartDate() > $now) {
      $context->buildViolation('Start date cannot be in the future')
        ->atPath('startDate')
        ->addViolation();
    }
  }
}
