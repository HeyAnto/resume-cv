<?php

namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CompanyType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('companyName', TextType::class, [
        'label' => 'Company Name',
        'attr' => [
          'placeholder' => 'Enter your company name',
          'class' => 'form-control',
          'data-counter' => 'companyName-counter',
          'data-max' => '98',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Company name is required']),
          new Assert\Length([
            'max' => 98,
            'maxMessage' => 'Company name cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Description',
        'required' => false,
        'attr' => [
          'placeholder' => 'Describe your company...',
          'class' => 'form-control',
          'data-counter' => 'description-counter',
          'data-max' => '500',
          'rows' => 4
        ],
        'constraints' => [
          new Assert\Length([
            'max' => 500,
            'maxMessage' => 'Description cannot be longer than {{ limit }} characters'
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
      ->add('websiteName', TextType::class, [
        'label' => 'Website Display Name',
        'required' => false,
        'attr' => [
          'placeholder' => 'My Company Website',
          'class' => 'form-control',
          'data-counter' => 'websiteName-counter',
          'data-max' => '98',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\Length([
            'max' => 98,
            'maxMessage' => 'Website name cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('websiteLink', UrlType::class, [
        'label' => 'Website URL',
        'required' => false,
        'attr' => [
          'placeholder' => 'https://example.com',
          'class' => 'form-control',
          'data-counter' => 'websiteLink-counter',
          'data-max' => '98',
          'autocomplete' => 'off'
        ],
        'constraints' => [
          new Assert\Url(['message' => 'Please enter a valid URL']),
          new Assert\Length([
            'max' => 98,
            'maxMessage' => 'Website URL cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('submit', SubmitType::class, [
        'label' => 'Create Company',
        'attr' => ['class' => 'btn btn-primary-xs full']
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Company::class,
    ]);
  }
}
