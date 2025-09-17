<?php

namespace App\Form;

use App\Entity\Volunteering;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VolunteeringFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('organizationName', TextType::class, [
        'label' => 'Organization Name',
        'attr' => [
          'placeholder' => 'Red Cross, Habitat for Humanity, etc.',
          'class' => 'form-control',
          'data-counter' => 'organizationName-counter',
          'data-max' => '98',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Organization name is required']),
          new Assert\Length([
            'max' => 98,
            'maxMessage' => 'Organization name cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('organizationLink', UrlType::class, [
        'label' => 'Organization Website',
        'required' => false,
        'attr' => [
          'placeholder' => 'https://www.redcross.org',
          'class' => 'form-control',
          'data-counter' => 'organizationLink-counter',
          'data-max' => '98',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\Length([
            'max' => 98,
            'maxMessage' => 'Organization website cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('role', TextType::class, [
        'label' => 'Role/Position',
        'attr' => [
          'placeholder' => 'Volunteer Coordinator, Team Leader, etc.',
          'class' => 'form-control',
          'data-counter' => 'role-counter',
          'data-max' => '32',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Role is required']),
          new Assert\Length([
            'max' => 32,
            'maxMessage' => 'Role cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('location', TextType::class, [
        'label' => 'Location',
        'attr' => [
          'placeholder' => 'New York, NY',
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
          'placeholder' => 'Describe your volunteering activities...',
          'class' => 'form-control',
          'rows' => 3,
          'data-counter' => 'description-counter',
          'data-max' => '160'
        ],
        'constraints' => [
          new Assert\Length([
            'max' => 160,
            'maxMessage' => 'Description cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('startDate', DateType::class, [
        'label' => 'Start Date',
        'widget' => 'single_text',
        'html5' => true,
        'input' => 'datetime_immutable',
        'attr' => [
          'class' => 'form-control'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Start date is required']),
          new Assert\LessThanOrEqual([
            'value' => new \DateTime(),
            'message' => 'Start date cannot be in the future'
          ])
        ]
      ])
      ->add('endDate', DateType::class, [
        'label' => 'End Date (Optional)',
        'widget' => 'single_text',
        'html5' => true,
        'input' => 'datetime_immutable',
        'required' => false,
        'attr' => [
          'class' => 'form-control'
        ],
        'constraints' => [
          new Assert\GreaterThan([
            'propertyPath' => 'parent.all[startDate].data',
            'message' => 'End date must be after start date'
          ])
        ]
      ])
      ->add('save', SubmitType::class, [
        'label' => 'Save Volunteering',
        'attr' => [
          'class' => 'btn btn-primary'
        ]
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Volunteering::class,
    ]);
  }
}
