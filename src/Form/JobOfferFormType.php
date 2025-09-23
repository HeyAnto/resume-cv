<?php

namespace App\Form;

use App\Entity\JobOffer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class JobOfferFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('title', TextType::class, [
        'label' => 'Job Title',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'e.g. Senior Developer, Marketing Manager...',
          'maxlength' => 98,
          'data-counter-target' => 'title',
          'data-counter-max' => '98'
        ],
        'constraints' => [
          new NotBlank(['message' => 'Please enter a job title.']),
          new Length([
            'min' => 2,
            'max' => 98,
            'minMessage' => 'Job title must be at least {{ limit }} characters long.',
            'maxMessage' => 'Job title cannot be longer than {{ limit }} characters.'
          ])
        ]
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Job Description',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'Describe the job requirements, responsibilities, and qualifications...',
          'rows' => 10,
          'data-counter-target' => 'description',
          'data-counter-max' => '2000'
        ],
        'constraints' => [
          new NotBlank(['message' => 'Please enter a job description.']),
          new Length([
            'min' => 10,
            'max' => 2000,
            'minMessage' => 'Job description must be at least {{ limit }} characters long.',
            'maxMessage' => 'Job description cannot be longer than {{ limit }} characters.'
          ])
        ]
      ])
      ->add('emailLink', EmailType::class, [
        'label' => 'Contact Email',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'contact@company.com',
          'maxlength' => 98
        ],
        'constraints' => [
          new NotBlank(['message' => 'Please enter a contact email.']),
          new Email(['message' => 'Please enter a valid email address.']),
          new Length([
            'max' => 98,
            'maxMessage' => 'Email cannot be longer than {{ limit }} characters.'
          ])
        ]
      ])
      ->add('submit', SubmitType::class, [
        'label' => $options['submit_label'] ?? 'Create Job Offer',
        'attr' => ['class' => 'btn btn-primary-xs full']
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => JobOffer::class,
      'submit_label' => 'Create Job Offer'
    ]);
  }
}
