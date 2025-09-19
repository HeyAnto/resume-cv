<?php

namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CompanyProfilePictureType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('profilePicture', FileType::class, [
        'label' => 'Company Logo',
        'mapped' => false,
        'required' => false,
        'attr' => [
          'accept' => 'image/*',
          'class' => 'form-control'
        ],
        'constraints' => [
          new Assert\File([
            'maxSize' => '2M',
            'mimeTypes' => [
              'image/jpeg',
              'image/png',
              'image/webp'
            ],
            'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, WebP)'
          ])
        ]
      ])
      ->add('submit', SubmitType::class, [
        'label' => 'Upload Logo',
        'attr' => ['class' => 'btn btn-secondary-xs full']
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Company::class,
    ]);
  }
}
