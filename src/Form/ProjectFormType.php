<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('projectName', TextType::class, [
        'label' => 'Project Name',
        'attr' => [
          'placeholder' => 'My Awesome Project',
          'class' => 'form-control',
          'data-counter' => 'projectName-counter',
          'data-max' => '98',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Project name is required']),
          new Assert\Length([
            'max' => 98,
            'maxMessage' => 'Project name cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('projectLink', UrlType::class, [
        'label' => 'Project URL',
        'attr' => [
          'placeholder' => 'https://myproject.com',
          'class' => 'form-control',
          'data-counter' => 'projectLink-counter',
          'data-max' => '98',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Project URL is required']),
          new Assert\Length([
            'max' => 98,
            'maxMessage' => 'Project URL cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Description',
        'attr' => [
          'placeholder' => 'Describe your project...',
          'class' => 'form-control',
          'rows' => 3,
          'data-counter' => 'description-counter',
          'data-max' => '160'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Description is required']),
          new Assert\Length([
            'max' => 160,
            'maxMessage' => 'Description cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('date', DateType::class, [
        'label' => 'Project Date',
        'widget' => 'single_text',
        'html5' => true,
        'input' => 'datetime_immutable',
        'attr' => [
          'class' => 'form-control'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Project date is required'])
        ]
      ])
      ->add('imagePath', FileType::class, [
        'label' => 'Project Image 1 (Optional)',
        'mapped' => false,
        'required' => false,
        'attr' => [
          'class' => 'form-control',
          'accept' => 'image/*'
        ],
        'constraints' => [
          new Assert\File([
            'maxSize' => '5M',
            'mimeTypes' => [
              'image/jpeg',
              'image/png',
              'image/webp',
              'image/gif'
            ],
            'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, WebP, or GIF)',
            'maxSizeMessage' => 'Image file is too large ({{ size }} {{ suffix }}). Max size is {{ limit }} {{ suffix }}.'
          ])
        ]
      ])
      ->add('imagePath2', FileType::class, [
        'label' => 'Project Image 2 (Optional)',
        'mapped' => false,
        'required' => false,
        'attr' => [
          'class' => 'form-control',
          'accept' => 'image/*'
        ],
        'constraints' => [
          new Assert\File([
            'maxSize' => '5M',
            'mimeTypes' => [
              'image/jpeg',
              'image/png',
              'image/webp',
              'image/gif'
            ],
            'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, WebP, or GIF)',
            'maxSizeMessage' => 'Image file is too large ({{ size }} {{ suffix }}). Max size is {{ limit }} {{ suffix }}.'
          ])
        ]
      ])
      ->add('imagePath3', FileType::class, [
        'label' => 'Project Image 3 (Optional)',
        'mapped' => false,
        'required' => false,
        'attr' => [
          'class' => 'form-control',
          'accept' => 'image/*'
        ],
        'constraints' => [
          new Assert\File([
            'maxSize' => '5M',
            'mimeTypes' => [
              'image/jpeg',
              'image/png',
              'image/webp',
              'image/gif'
            ],
            'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, WebP, or GIF)',
            'maxSizeMessage' => 'Image file is too large ({{ size }} {{ suffix }}). Max size is {{ limit }} {{ suffix }}.'
          ])
        ]
      ])
      ->add('save', SubmitType::class, [
        'label' => 'Save Project',
        'attr' => [
          'class' => 'btn btn-primary'
        ]
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Project::class,
    ]);
  }
}
