<?php

namespace App\Form;

use App\Entity\CompanyTag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CompanyTagFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('label', TextType::class, [
        'label' => false,
        'attr' => [
          'placeholder' => 'Enter tag name',
          'maxlength' => 48,
          'class' => 'form-control'
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Tag name cannot be empty',
          ]),
          new Length([
            'min' => 2,
            'max' => 48,
            'minMessage' => 'Tag name must be at least {{ limit }} characters long',
            'maxMessage' => 'Tag name cannot be longer than {{ limit }} characters',
          ]),
        ]
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => CompanyTag::class,
    ]);
  }
}
