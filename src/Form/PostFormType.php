<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PostFormType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options): void
  {
    $builder
      ->add('description', TextareaType::class, [
        'label' => 'Description',
        'attr' => [
          'placeholder' => 'Share your thoughts, ideas, or experiences...',
          'class' => 'form-control',
          'rows' => 5,
          'data-counter' => 'description-counter',
          'data-max' => '280'
        ],
        'constraints' => [
          new Assert\NotBlank(['message' => 'Description is required']),
          new Assert\Length([
            'max' => 280,
            'maxMessage' => 'Description cannot be longer than {{ limit }} characters'
          ])
        ]
      ])
      ->add('imageFile', FileType::class, [
        'label' => 'Image (Optional)',
        'required' => false,
        'mapped' => false,
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
      ->add('isVisible', CheckboxType::class, [
        'label' => 'Make this post visible to others',
        'required' => false,
        'attr' => [
          'class' => 'form-check-input'
        ]
      ])
      ->add('submit', SubmitType::class, [
        'label' => 'Save Post'
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void
  {
    $resolver->setDefaults([
      'data_class' => Post::class,
    ]);
  }
}
