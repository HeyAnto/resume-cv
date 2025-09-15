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

class ExperienceFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('companyName', TextType::class, [
        'label' => 'Company Name',
        'attr' => [
          'placeholder' => 'Google, Microsoft, etc.',
          'class' => 'form-control'
        ]
      ])
      ->add('companyLink', UrlType::class, [
        'label' => 'Company Website',
        'required' => false,
        'attr' => [
          'placeholder' => 'https://www.example.com',
          'class' => 'form-control'
        ]
      ])
      ->add('job', TextType::class, [
        'label' => 'Job Title',
        'attr' => [
          'placeholder' => 'Full Stack Developer',
          'class' => 'form-control'
        ]
      ])
      ->add('location', TextType::class, [
        'label' => 'Location',
        'attr' => [
          'placeholder' => 'Paris, France',
          'class' => 'form-control'
        ]
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Description (Optional)',
        'required' => false,
        'attr' => [
          'placeholder' => 'Describe your missions and achievements...',
          'class' => 'form-control',
          'rows' => 3
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
    ]);
  }
}
